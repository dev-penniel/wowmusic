<?php

use Livewire\Attributes\On;
use Livewire\Volt\Component;
use App\Models\Track;

new class extends Component {

    public int|null $currentTrackId = null;
    public bool $shuffle = false;
    public bool $isPlaying = false;

    public array $tracksForJs = [];
    public int|null $currentIdForJs = null;

    public function mount(): void
    {
        $tracks = Track::orderBy('id')->get();
        $this->tracksForJs = $tracks->map(fn($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'artist' => $t->artist,
            'album' => $t->album,
            'file_path' => $t->file_path,
            'cover_path' => $t->cover_path,
            'duration' => $t->duration,
        ])->all();

        $first = $tracks->first();
        $this->currentTrackId = $first ? $first->id : null;
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

};
?>

@php
// Access component variables via get_object_vars($this)
$data = get_object_vars($this);
$tracks = $data['tracksForJs'] ?? [];
$currentId = $data['currentTrackId'] ?? null;
@endphp

<!-- JSON data for Alpine -->
<script type="application/json" id="sp_tracks_json">{!! json_encode($tracks) !!}</script>

<div class="min-h-screen bg-gray-900 text-gray-100 p-4">
  <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Playlist / Library -->
    <div class="md:col-span-2 bg-gray-800 rounded-lg p-4">
      <h2 class="text-xl font-semibold mb-4">Library</h2>

      <div class="space-y-3">
        @foreach ($tracks as $track)
          <div wire:click="setTrack({{ $track['id'] }})"
               class="flex items-center gap-4 p-3 rounded hover:bg-gray-700 cursor-pointer {{ $currentId === $track['id'] ? 'bg-gray-700' : '' }}">
            <img src="{{ $track['cover_path'] ? asset('storage/'.$track['cover_path']) : asset('images/default-cover.jpg') }}" class="w-14 h-14 rounded object-cover">
            <div class="flex-1">
              <div class="text-sm font-medium">{{ $track['title'] }}</div>
              <div class="text-xs text-gray-400">{{ $track['artist'] }} · {{ $track['album'] }}</div>
            </div>
            <div class="text-xs text-gray-400">
              @php
                $m = floor(($track['duration'] ?? 0) / 60);
                $s = str_pad(($track['duration'] ?? 0) % 60, 2, '0', STR_PAD_LEFT);
              @endphp
              {{ $m }}:{{ $s }}
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <!-- Now Playing -->
    <div class="bg-gray-800 rounded-lg p-4 flex flex-col">
      <div class="text-sm text-gray-400 mb-2">Now Playing</div>

      <div
        x-data="playerComponentFromJson('#sp_tracks_json', {{ $currentId ?? 'null' }})"
        x-init="init()"
        class="flex-1 flex flex-col"
      >
        <div class="flex items-center gap-4">
          <img :src="currentCover" alt="cover" class="w-28 h-28 rounded object-cover">
          <div>
            <div class="text-lg font-semibold" x-text="currentTitle"></div>
            <div class="text-sm text-gray-400" x-text="currentArtist"></div>
          </div>
        </div>

        <!-- Controls -->
        <div class="mt-6">
          <div class="flex items-center justify-center gap-6">
            <button @click="prev()" class="p-2 hover:text-white text-gray-300">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
              </svg>


            </button>

            <button @click="togglePlay()" class="bg-white text-gray-900 rounded-full w-14 h-14 flex items-center justify-center shadow">
              <template x-if="!isPlaying">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3.868v16.264L19 12 5 3.868z"/></svg>
              </template>
              <template x-if="isPlaying">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
              </template>
            </button>

            <button @click="next()" class="p-2 hover:text-white text-gray-300">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
              </svg>
            </button>
          </div>

          <!-- Progress -->
          <div class="mt-6">
            <div class="flex items-center gap-2 text-xs text-gray-400">
              <div x-text="formatTime(currentTime)"></div>
              <div class="flex-1">
                <div class="w-full relative h-1 bg-gray-700 rounded" @click="seekTo($event)">
                  <div :style="`width:${progress}%`" class="h-1 bg-gray-900 rounded"></div>
                </div>
              </div>
              <div x-text="formatTime(duration)"></div>
            </div>
          </div>

        
          <div class="mt-4 flex items-center gap-3">
            <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M5 9v6h4l5 5V4L9 9H5z"/></svg>
            <input type="range" min="0" max="1" step="0.01" x-model.number="volume" @input="updateVolume()" class="w-full">
          </div>
        </div>

        <audio x-ref="audio" class="hidden"></audio>
      </div>
    </div>
  </div>

  <!-- Mini player -->
  <!-- Mini player (drop-in replacement) -->
<div
  class="fixed bottom-0 left-0 right-0 bg-gray-800 border-t border-gray-700 p-2 z-50"
  x-data="playerComponentFromJson('#sp_tracks_json', {{ $currentId ?? 'null' }})"
  x-init="init()"
>
  <div class="max-w-6xl mx-auto flex items-center gap-4">
    <!-- cover: uses Alpine currentCover, falls back to blade asset if not set -->
    <img
      :src="currentCover || '{{ $tracks[0]['cover_path'] ?? asset('images/default-cover.jpg') }}'"
      alt="cover"
      class="w-12 h-12 rounded object-cover"
    />

    <!-- title / artist -->
    <div class="flex-1 min-w-0">
      <div class="text-sm font-medium truncate" x-text="currentTitle || '{{ $tracks[0]['title'] ?? '—' }}'"></div>
      <div class="text-xs text-gray-400 truncate" x-text="currentArtist || '{{ $tracks[0]['artist'] ?? '' }}'"></div>
    </div>

    <!-- play/pause + optional prev/next -->
    <div class="flex items-center gap-2">
      <!-- prev (optional) -->
      <button
        @click="prev()"
        class="p-2 hover:text-white text-gray-300"
        title="Previous"
        aria-label="Previous track"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
      </button>

      <!-- play/pause -->
      <button
        @click="togglePlay()"
        :title="isPlaying ? 'Pause' : 'Play'"
        class="bg-white text-gray-900 rounded-full w-12 h-12 flex items-center justify-center shadow"
        aria-label="Play or pause"
      >
        <template x-if="!isPlaying">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3.868v16.264L19 12 5 3.868z"/></svg>
        </template>
        <template x-if="isPlaying">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
        </template>
      </button>

      <!-- next (optional) -->
      <button
        @click="next()"
        class="p-2 hover:text-white text-gray-300"
        title="Next"
        aria-label="Next track"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
      </button>
    </div>
  </div>

  <!-- optional small progress bar under the player -->
  <div class="max-w-6xl mx-auto mt-2 px-2">
    <div class="flex items-center gap-2 text-xs text-gray-400">
      <div x-text="formatTime(currentTime)"></div>
      <div class="flex-1">
        <div class="w-full relative h-1 bg-gray-700 rounded cursor-pointer" @click="seekTo($event)">
          <div :style="`width:${progress}%`" class="h-1 bg-gray-900 rounded"></div>
        </div>
      </div>
      <div x-text="formatTime(duration)"></div>
    </div>
  </div>

  <!-- keep the audio element reference inside this component so the component can control it -->
  <audio x-ref="audio" class="hidden"></audio>
</div>

</div>

<script>
function playerComponentFromJson(jsonSelector, currentId) {
    const script = document.querySelector(jsonSelector);
    let tracks = [];
    try { tracks = script ? JSON.parse(script.textContent) : []; } 
    catch (e) { console.error('Invalid tracks JSON', e); tracks = []; }

    return {
        tracks: tracks || [],
        currentTrack: null,
        currentIndex: 0,
        currentTitle: '',
        currentArtist: '',
        currentCover: '',
        isPlaying: false,
        progress: 0,
        duration: 0,
        currentTime: 0,
        volume: 0.8,

        init() {
            if (currentId !== null) this.currentIndex = this.tracks.findIndex(t => t.id === currentId);
            if (this.currentIndex < 0) this.currentIndex = 0;
            this.loadTrack(this.currentIndex);

            Livewire.on('trackChanged', id => { const idx = this.tracks.findIndex(t => t.id === id); if (idx>=0){ this.loadTrack(idx); this.play(); } });

            const audio = this.$refs.audio;
            audio.volume = this.volume;

            audio.addEventListener('timeupdate', ()=>{ this.currentTime = Math.floor(audio.currentTime); this.duration = Math.floor(audio.duration||0); this.progress = this.duration?(audio.currentTime/audio.duration)*100:0; });
            audio.addEventListener('ended', ()=>{ this.next(); });
        },

        loadTrack(index) {
            if (!this.tracks[index]) return;
            this.currentIndex = index;
            const t = this.tracks[index];
            this.currentTrack = t;
            this.currentTitle = t.title;
            this.currentArtist = t.artist;
            this.currentCover = t.cover_path?(`/storage/${t.cover_path}`):'/images/default-cover.jpg';
            const audio = this.$refs.audio;
            audio.src = `/storage/${t.file_path}`;
            audio.load();
            this.isPlaying = false;

            const miniCover = document.getElementById('miniCover');
            const miniTitle = document.getElementById('miniTitle');
            const miniArtist = document.getElementById('miniArtist');
            if(miniCover) miniCover.src=this.currentCover;
            if(miniTitle) miniTitle.textContent=this.currentTitle;
            if(miniArtist) miniArtist.textContent=this.currentArtist;
        },

        play() { const audio=this.$refs.audio; const p=audio.play(); if(p&&typeof p.then==='function'){p.then(()=>{this.isPlaying=true;}).catch(()=>{this.isPlaying=false;});}else{this.isPlaying=true;} },
        pause(){ this.$refs.audio.pause(); this.isPlaying=false; },
        togglePlay(){ if(this.isPlaying) this.pause(); else this.play(); },
        next(){ let idx=(this.currentIndex+1)%this.tracks.length; this.loadTrack(idx); this.play(); },
        prev(){ let idx=this.currentIndex-1; if(idx<0) idx=this.tracks.length-1; this.loadTrack(idx); this.play(); },
        seekTo(e){ const rect=e.currentTarget.getBoundingClientRect(); const ratio=(e.clientX-rect.left)/rect.width; const audio=this.$refs.audio; if(audio.duration) audio.currentTime=ratio*audio.duration; },
        updateVolume(){ this.$refs.audio.volume=this.volume; },
        formatTime(seconds){ if(!seconds||isNaN(seconds)) return '0:00'; const m=Math.floor(seconds/60); const s=Math.floor(seconds%60).toString().padStart(2,'0'); return `${m}:${s}`; }
    }
}
</script>
