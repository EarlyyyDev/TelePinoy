<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminChannelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_returns_a_token_and_protects_channel_management(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => Hash::make('a-safe-test-password'),
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'a-safe-test-password',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['email']]);

        $this->getJson('/api/admin/channels')->assertUnauthorized();

        $this->withToken($response->json('token'))
            ->getJson('/api/admin/channels')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_non_admin_tokens_cannot_manage_channels(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-user')->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/channels')->assertForbidden();
    }

    public function test_playlist_import_creates_inactive_channels_and_updates_by_tvg_id(): void
    {
        $streamUrl = 'https://example.test/live.m3u8?token='.str_repeat('a', 260);

        Http::fake([
            'https://iptv-org.github.io/iptv/countries/ph.m3u' => Http::response(
                "#EXTM3U\n#EXTINF:-1 tvg-id=\"demo.ph\" tvg-logo=\"https://example.test/logo.png\" group-title=\"News\",Demo News\n{$streamUrl}\n",
                200
            ),
        ]);

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $token = $admin->createToken('test', ['admin'])->plainTextToken;

        $this->withToken($token)->postJson('/api/admin/channels/import-iptv-org', [
            'url' => 'https://iptv-org.github.io/iptv/countries/ph.m3u',
        ])
            ->assertOk()
            ->assertJsonPath('created', 1)
            ->assertJsonPath('updated', 0);

        $channel = Channel::query()->where('tvg_id', 'demo.ph')->firstOrFail();
        $this->assertSame('Demo News', $channel->name);
        $this->assertSame('News', $channel->category);
        $this->assertSame('https://example.test/logo.png', $channel->logo_url);
        $this->assertSame($streamUrl, $channel->stream_url);
        $this->assertFalse($channel->is_active);

        $this->withToken($token)->postJson('/api/admin/channels/import-iptv-org', [
            'url' => 'https://iptv-org.github.io/iptv/countries/ph.m3u',
        ])
            ->assertOk()
            ->assertJsonPath('created', 0)
            ->assertJsonPath('updated', 1);

        $this->assertSame(1, Channel::query()->count());
    }

    public function test_admin_can_only_enable_hls_streams(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $token = $admin->createToken('test', ['admin'])->plainTextToken;
        $channel = Channel::query()->create([
            'name' => 'DASH Test',
            'slug' => 'dash-test',
            'category' => 'General',
            'description' => 'Test stream',
            'stream_url' => 'https://example.test/live.mpd',
            'is_active' => false,
        ]);

        $this->withToken($token)->putJson('/api/admin/channels/dash-test/playability', [
            'is_active' => true,
        ])->assertUnprocessable();

        $this->assertFalse($channel->fresh()->is_active);

        $channel->update(['stream_url' => 'https://example.test/live.m3u8']);
        $this->withToken($token)->postJson('/api/admin/channels/dash-test/test-success')->assertOk();
        $this->withToken($token)->putJson('/api/admin/channels/dash-test/playability', [
            'is_active' => true,
        ])->assertOk()->assertJsonPath('data.is_active', true);
    }

    public function test_dashboard_counts_published_channels_imported_drafts_and_play_starts(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $token = $admin->createToken('test', ['admin'])->plainTextToken;

        Channel::query()->create([
            'name' => 'Published',
            'slug' => 'published',
            'category' => 'General',
            'description' => 'Published channel',
            'stream_url' => 'https://example.test/published.m3u8',
            'is_active' => true,
        ]);
        Channel::query()->create([
            'name' => 'Imported draft',
            'slug' => 'imported-draft',
            'source' => 'imported',
            'category' => 'General',
            'description' => 'Unpublished import',
            'stream_url' => 'https://example.test/imported.m3u8',
            'is_active' => false,
        ]);

        $this->withToken($token)->postJson('/api/channels/published/views')
            ->assertAccepted();

        $this->withToken($token)->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_channels', 2)
            ->assertJsonPath('data.live_channels', 1)
            ->assertJsonPath('data.imported_channels', 1)
            ->assertJsonPath('data.viewer_starts_this_month', 1);
    }
}
