<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\Track;
use Livewire\Attributes\Layout;

new 
#[Layout('components.layouts.app.frontend')]
class extends Component {

    use WithFileUploads;

    public $audio;

    // Rename method so it doesn't conflict with the property
    public function saveAudio()
    {

        dd($this->audio);

    }

};
?>



<div class="w-full p-20"> {{-- removed wire:ignore --}}

    <form wire:submit.prevent="saveAudio"> {{-- added .prevent --}}
        
        <input
            type="file"
            accept="audio/*"
            wire:model="audio"
            class="block w-full text-sm"
        >
            
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </form>

    @if ($audio)
        <audio controls class="mt-4">
            <source src="{{ $audio->temporaryUrl() }}">
        </audio>
    @endif

</div>
