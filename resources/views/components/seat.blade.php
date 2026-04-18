@props([
    'player',
    'currentPlayerId' => null,
    'rotate' => '0',
    'class' => '',
])

@php
    $swap = in_array((string) $rotate, ['90', '-90']);
    $rotateClass = match((string) $rotate) {
        '180' => 'rotate-180',
        '90' => 'rotate-90',
        '-90' => '-rotate-90',
        default => '',
    };
@endphp

<div class="relative w-full h-full min-h-[120px] {{ $class }}" style="container-type: size;">
    <div class="absolute inset-0 flex items-center justify-center">
        <div class="{{ $rotateClass }} origin-center transition-transform duration-500 rounded-[32px] overflow-hidden"
             style="{{ $swap ? 'width: 100cqh; height: 100cqw;' : 'width: 100cqw; height: 100cqh;' }}">
            <livewire:game.player-card :player="$player" :is-current-player="$player->id === $currentPlayerId" :key="'player-'.$player->id.uniqid()" />
        </div>
    </div>
</div>
