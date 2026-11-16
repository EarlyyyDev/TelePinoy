<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            [
                'name' => 'GMA 7',
                'slug' => 'gma-7',
                'category' => 'General',
                'description' => 'A flagship Philippine broadcast channel known for entertainment, news, and family programming.',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7d/GMA_Network_Logo.svg/512px-GMA_Network_Logo.svg.png',
                'stream_url' => 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8',
                'is_featured' => true,
            ],
            [
                'name' => 'ABS-CBN',
                'slug' => 'abs-cbn',
                'category' => 'Entertainment',
                'description' => 'One of the leading Philippine television networks featuring dramas, variety shows, and news.',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5b/ABS-CBN_Logo.svg/512px-ABS-CBN_Logo.svg.png',
                'stream_url' => 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8',
                'is_featured' => true,
            ],
            [
                'name' => 'TV5',
                'slug' => 'tv5',
                'category' => 'News',
                'description' => 'A dynamic national channel providing current affairs, public service, and sports coverage.',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7c/TV5_%28Philippines%29_logo.svg/512px-TV5_%28Philippines%29_logo.svg.png',
                'stream_url' => 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8',
                'is_featured' => false,
            ],
            [
                'name' => 'One News',
                'slug' => 'one-news',
                'category' => 'News',
                'description' => 'Focused on up-to-the-minute political and business updates for Filipino audiences.',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/One_News_ph_logo.svg/512px-One_News_ph_logo.svg.png',
                'stream_url' => 'https://test-streams.mux.dev/eka4m9u4m0j0.m3u8',
                'is_featured' => true,
            ],
            [
                'name' => 'PBA Rush',
                'slug' => 'pba-rush',
                'category' => 'Sports',
                'description' => 'Covers local basketball action and major sporting events across the Philippines.',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5d/PBA_Rush_logo.svg/512px-PBA_Rush_logo.svg.png',
                'stream_url' => 'https://test-streams.mux.dev/playlist.m3u8',
                'is_featured' => true,
            ],
            [
                'name' => 'Cinema One',
                'slug' => 'cinema-one',
                'category' => 'Movies',
                'description' => 'Showcases classic cinema, indie features, and curated film content for movie lovers.',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5d/Cinema_One_logo.svg/512px-Cinema_One_logo.svg.png',
                'stream_url' => 'https://test-streams.mux.dev/playlist.m3u8',
                'is_featured' => false,
            ],
            [
                'name' => 'DepEd TV',
                'slug' => 'deped-tv',
                'category' => 'Educational',
                'description' => 'Delivers educational programs and learning resources for students and families.',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/DepEd_TV_Logo.svg/512px-DepEd_TV_Logo.svg.png',
                'stream_url' => 'https://test-streams.mux.dev/playlist.m3u8',
                'is_featured' => false,
            ],
            [
                'name' => 'ETC',
                'slug' => 'etc',
                'category' => 'Entertainment',
                'description' => 'An entertainment-focused channel mixing pop culture, lifestyle, and youth-driven content.',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/ETC_logo.svg/512px-ETC_logo.svg.png',
                'stream_url' => 'https://test-streams.mux.dev/playlist.m3u8',
                'is_featured' => false,
            ],
        ];

        foreach ($channels as $channel) {
            Channel::query()->updateOrCreate(
                ['slug' => $channel['slug']],
                $channel
            );
        }
    }
}
