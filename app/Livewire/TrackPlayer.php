<?php

namespace App\Http\Livewire;

use Livewire\Attributes\On;
use Livewire\Volt\Component;
use App\Models\Track;

class TrackPlayer extends Component
{
    public int|null $currentTrackId = null;
    public bool $shuffle = false;
    public bool $isPlaying = false;

    public array $tracksForJs = [];
    public int|null $currentIdForJs = null;

    public $tracks = [];

    public function mount(): void
    {
        $this->tracks = Track::orderBy('id')->get()->toArray();

        $this->tracksForJs = array_map(fn($t) => [
            'id' => $t['id'],
            'title' => $t['title'],
            'artist' => $t['artist'],
            'album' => $t['album'],
            'file_path' => $t['file_path'],
            'cover_path' => $t['cover_path'],
            'duration' => $t['duration'],
        ], $this->tracks);

        $first = $this->tracks[0] ?? null;
        $this->currentTrackId = $first['id'] ?? null;
        $this->currentIdForJs = $this->currentTrackId;
    }

    #[On('tracks-updated')]
    public function getTracksProperty()
    {
        return Track::orderBy('id')->get();
    }

    public function setTrack(int $id): void
    {
        $this->currentTrackId = $id;
        $this->currentIdForJs = $id;
        $this->emit('trackChanged', $id);
    }
}
