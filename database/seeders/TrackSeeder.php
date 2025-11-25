<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Track;

class TrackSeeder extends Seeder
{
    public function run()
    {
        // Adjust file_path/cover_path to files you uploaded into storage/app/public/tracks & covers
        Track::create([
            'title' => 'Sunrise Groove',
            'artist' => 'Tlhono Beats',
            'album' => 'Early Light',
            'file_path' => 'tracks/sunrise-groove.mp3',
            'cover_path' => 'covers/sunrise.jpg',
            'duration' => 170,
        ]);

        Track::create([
            'title' => 'Midnight Drive',
            'artist' => 'Moonwalker',
            'album' => 'Night Roads',
            'file_path' => 'tracks/midnight-drive.mp3',
            'cover_path' => 'covers/midnight.jpg',
            'duration' => 204,
        ]);

        // add more...
    }
}
