<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Channel::query()->where('is_active', true);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($searchQuery) use ($term) {
                $searchQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $channels = $query->orderBy('name')->get();

        return response()->json([
            'data' => $channels,
            'meta' => [
                'count' => $channels->count(),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $channel = Channel::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'data' => $channel,
        ]);
    }

    public function recordView(string $slug)
    {
        $channel = Channel::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        DB::table('channel_view_events')->insert([
            'channel_id' => $channel->id,
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Playback start recorded.'], 202);
    }

    public function adminDashboard()
    {
        return response()->json([
            'data' => [
                'total_channels' => Channel::query()->count(),
                'live_channels' => Channel::query()->where('is_active', true)->count(),
                'imported_channels' => Channel::query()->where('source', 'imported')->where('is_active', false)->count(),
                'custom_drafts' => Channel::query()->where('source', 'custom')->where('is_active', false)->count(),
                'viewer_starts_this_month' => DB::table('channel_view_events')
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
            ],
        ]);
    }

    public function adminIndex()
    {
        return response()->json([
            'data' => Channel::query()->orderBy('name')->get(),
        ]);
    }

    public function importIptvOrg(Request $request)
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);
        $url = $validated['url'];
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $allowedHosts = array_map('strtolower', config('telepinoy.playlist_hosts', []));

        if ($scheme !== 'https' || ! in_array($host, $allowedHosts, true)) {
            return response()->json([
                'message' => 'Use an HTTPS playlist URL from an approved host. Configure additional hosts in TELEPINOY_PLAYLIST_HOSTS.',
            ], 422);
        }

        $response = Http::withoutRedirecting()->timeout(30)->get($url);

        if (! $response->successful()) {
            return response()->json(['message' => 'The IPTV-org playlist could not be fetched.'], 502);
        }

        $playlist = $response->body();
        if (strlen($playlist) > 10 * 1024 * 1024 || ! str_starts_with(ltrim($playlist), '#EXTM3U')) {
            return response()->json(['message' => 'The playlist is invalid or exceeds the import size limit.'], 422);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $entry = null;

        foreach (preg_split('/\r\n|\r|\n/', $playlist) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                if (str_starts_with($line, '#EXTINF:')) {
                    $entry = $this->parsePlaylistEntry($line);
                }

                continue;
            }

            if (! $entry || ! filter_var($line, FILTER_VALIDATE_URL) || ! in_array(parse_url($line, PHP_URL_SCHEME), ['http', 'https'], true)) {
                $skipped++;
                $entry = null;

                continue;
            }

            $name = $entry['name'];
            $slug = Str::slug($name);
            if ($slug === '') {
                $skipped++;
                $entry = null;

                continue;
            }

            $channel = null;
            if ($entry['tvg_id']) {
                $channel = Channel::query()->where('tvg_id', $entry['tvg_id'])->first();
            }
            $channel ??= Channel::query()->where('slug', $slug)->first();

            if (! $channel && Channel::query()->where('slug', $slug)->exists()) {
                $suffix = $entry['tvg_id'] ?: substr(hash('sha256', $line), 0, 8);
                $slug = Str::slug($name.'-'.$suffix);
            }

            $sameStream = $channel && $channel->stream_url === $line;
            $attributes = [
                'name' => $name,
                'slug' => $channel?->slug ?? $slug,
                'tvg_id' => $entry['tvg_id'] ?: null,
                'source' => 'imported',
                'category' => $entry['category'] ?: 'General',
                'description' => $channel?->description ?: 'Imported from IPTV-org. Confirm playback availability and streaming rights before use.',
                'logo_url' => $entry['logo_url'] ?: null,
                'stream_url' => $line,
                'is_featured' => $channel?->is_featured ?? false,
                'is_active' => $sameStream ? $channel->is_active : false,
                'tested_stream_url' => $sameStream ? $channel->tested_stream_url : null,
                'stream_tested_at' => $sameStream ? $channel->stream_tested_at : null,
            ];

            if ($channel) {
                $channel->fill($attributes)->save();
                $updated++;
            } else {
                Channel::query()->create($attributes);
                $created++;
            }

            $entry = null;
        }

        return response()->json([
            'message' => 'Playlist import completed. Imported channels remain unpublished until tested and published.',
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }

    public function markStreamTested(string $slug)
    {
        $channel = Channel::query()->where('slug', $slug)->firstOrFail();
        $path = parse_url((string) $channel->stream_url, PHP_URL_PATH);

        if (! is_string($path) || ! str_ends_with(strtolower($path), '.m3u8')) {
            return response()->json(['message' => 'Only HLS .m3u8 streams can be tested by this app.'], 422);
        }

        $channel->tested_stream_url = $channel->stream_url;
        $channel->stream_tested_at = now();
        $channel->save();

        return response()->json(['data' => $channel->fresh()]);
    }

    public function setPlayability(Request $request, string $slug)
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $channel = Channel::query()->where('slug', $slug)->firstOrFail();
        $path = parse_url((string) $channel->stream_url, PHP_URL_PATH);

        if ($validated['is_active'] && (! is_string($path) || ! str_ends_with(strtolower($path), '.m3u8'))) {
            return response()->json([
                'message' => 'Only HLS .m3u8 streams can be marked playable by this app.',
            ], 422);
        }

        if ($validated['is_active'] && (
            ! $channel->stream_tested_at
            || $channel->tested_stream_url !== $channel->stream_url
        )) {
            return response()->json([
                'message' => 'Test the current stream successfully before publishing this channel.',
            ], 422);
        }

        $channel->is_active = $validated['is_active'];
        $channel->save();

        return response()->json(['data' => $channel->fresh()]);
    }

    /**
     * Store a newly created channel in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('channels', 'slug')],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string'],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'stream_url' => ['nullable', 'url', 'max:8192'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] ??= Str::slug($validated['name']);
        $validated['is_active'] = false;
        $validated['source'] = 'custom';

        $channel = Channel::create($validated);

        return response()->json([
            'data' => $channel,
            'message' => 'Channel created successfully.',
        ], 201);
    }

    /**
     * Update the specified channel in storage.
     */
    public function update(Request $request, string $slug)
    {
        $channel = Channel::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'slug' => ['sometimes', 'required', 'string', 'max:120', 'alpha_dash', Rule::unique('channels', 'slug')->ignore($channel->id)],
            'category' => ['sometimes', 'required', 'string', 'max:80'],
            'description' => ['sometimes', 'required', 'string'],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'stream_url' => ['nullable', 'url', 'max:8192'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('stream_url', $validated) && $validated['stream_url'] !== $channel->stream_url) {
            $channel->is_active = false;
            $channel->tested_stream_url = null;
            $channel->stream_tested_at = null;
        }

        if (! empty($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $channel->fill($validated);
        $channel->save();

        return response()->json([
            'data' => $channel->fresh(),
            'message' => 'Channel updated successfully.',
        ]);
    }

    /**
     * Remove the specified channel from storage.
     */
    public function destroy(string $slug)
    {
        $channel = Channel::query()->where('slug', $slug)->firstOrFail();
        $channel->delete();

        return response()->json([
            'message' => 'Channel deleted successfully.',
        ]);
    }

    private function parsePlaylistEntry(string $line): ?array
    {
        $comma = strpos($line, ',');
        if ($comma === false) {
            return null;
        }

        $metadata = substr($line, 0, $comma);
        $attributes = [];
        preg_match_all('/([a-zA-Z0-9_-]+)="([^"]*)"/', $metadata, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attributes[$match[1]] = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5);
        }

        $name = trim(substr($line, $comma + 1));
        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'tvg_id' => trim($attributes['tvg-id'] ?? ''),
            'logo_url' => trim($attributes['tvg-logo'] ?? ''),
            'category' => trim($attributes['group-title'] ?? ''),
        ];
    }
}
