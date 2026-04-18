<div class="p-2 sm:p-4 h-[calc(100vh-4rem)] flex flex-col"
     x-data
     @visibilitychange.window="if (document.visibilityState === 'visible') $dispatch('refresh-board')"
     wire:poll.10s="$dispatch('refresh-board')">
    @if(!$currentPlayer && !$isSpectator)
        <!-- Join Game Form Modal/Screen -->
        <div class="flex items-center justify-center h-full">
            <flux:card class="w-full max-w-sm space-y-4">
                <div class="text-center mb-4">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Join Game: {{ $game->code }}</h2>
                    <p class="text-zinc-500 mt-1">Pick your name and color</p>
                </div>
                
                @if($players->count() > 0)
                    <div class="space-y-2 mb-6">
                        <flux:label>Oder als bestehender Spieler joinen:</flux:label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($players as $existing)
                                <button type="button" wire:click="joinAsExisting({{ $existing->id }})" class="px-3 py-1.5 rounded-full text-sm font-semibold text-white shadow hover:opacity-90 transition-opacity" style="background-color: {{ $existing->color }}">
                                    {{ $existing->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="h-px bg-zinc-200 dark:bg-zinc-800 flex-1"></div>
                        <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Oder neuer Spieler</span>
                        <div class="h-px bg-zinc-200 dark:bg-zinc-800 flex-1"></div>
                    </div>
                @endif
                
                <form wire:submit="joinAsPlayer" class="space-y-4 mt-2">
                    <flux:input wire:model="playerName" label="Your Name" placeholder="e.g. Teferi" required />
                    
                    <div>
                        <flux:label>Color</flux:label>
                        <div class="flex gap-2 mt-2 flex-wrap">
                            @foreach(['#ef4444', '#3b82f6', '#22c55e', '#a855f7', '#f97316', '#71717a'] as $color)
                                <button type="button" 
                                    wire:click="$set('playerColor', '{{ $color }}')"
                                    class="w-10 h-10 rounded-full border-2 transition-transform {{ $playerColor === $color ? 'scale-110 border-zinc-900 dark:border-white shadow-lg' : 'border-transparent' }}"
                                    style="background-color: {{ $color }}"
                                    aria-label="Select color {{ $color }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>
                    
                    <flux:button type="submit" variant="primary" class="w-full mt-4">
                        Enter Game
                    </flux:button>
                </form>
                
                <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button variant="ghost" class="w-full" wire:click="$set('isSpectator', true)" icon="eye">
                        Nur zuschauen (als Zuschauer)
                    </flux:button>
                </div>
            </flux:card>
        </div>
    @else
        <!-- Game Table -->
        <div class="flex flex-col h-full">
            <div class="flex justify-between items-center mb-4 z-10 relative">
                <div class="flex items-center gap-2">
                    <h1 class="font-bold text-xl dark:text-zinc-200">{{ $game->code }}</h1>
                    <flux:badge size="sm" variant="success">Live</flux:badge>
                </div>
                <div class="flex gap-2">
                    <flux:dropdown>
                        <flux:button variant="subtle" icon="arrow-path" size="sm" class="px-2" aria-label="Drehen" title="Ansicht drehen"></flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="$set('boardRotation', 0)">0° (Normal)</flux:menu.item>
                            <flux:menu.item wire:click="$set('boardRotation', 90)">90° (Rechts)</flux:menu.item>
                            <flux:menu.item wire:click="$set('boardRotation', 180)">180° (Kopfhörer)</flux:menu.item>
                            <flux:menu.item wire:click="$set('boardRotation', 270)">270° (Links)</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    <flux:dropdown>
                        <flux:button variant="subtle" icon="squares-2x2" size="sm">Layout</flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="$set('viewLayout', 'grid')">Grid (Standard)</flux:menu.item>
                            <flux:menu.item wire:click="$set('viewLayout', 'focused')">Eigener Fokus</flux:menu.item>
                            <flux:menu.item wire:click="$set('viewLayout', '6-rect')">Rechteck-Tisch (Mitte)</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
            
            <div class="flex-1 w-full relative" style="container-type: size;">
                <div class="absolute inset-0 flex items-center justify-center overflow-visible">
                    <div class="transition-transform duration-500 ease-in-out flex"
                         style="transform: rotate({{ $boardRotation }}deg); {{ ($boardRotation === 90 || $boardRotation === 270) ? 'width: 100cqh; height: 100cqw;' : 'width: 100cqw; height: 100cqh;' }}">
                    
                        @php
                            // Sort players based on game settings player_order
                            $orderedPlayers = clone $players;
                            if (!empty($game->settings['player_order'])) {
                                $order = $game->settings['player_order'];
                                $orderedPlayers = $orderedPlayers->sortBy(function($p) use ($order) {
                                    $idx = array_search($p->id, $order);
                                    return $idx !== false ? $idx : 999;
                                })->values();
                            }
                            $playerCount = $orderedPlayers->count();
                        @endphp

                        @if($viewLayout === '6-rect')
                <!-- Smart Rectangular Table Layout for iPad/Tablets -->
                <div class="flex-1 w-full h-full flex flex-col gap-2 sm:gap-4 overflow-hidden">
                    @if($playerCount === 0)
                        <div class="rounded-[32px] flex-1 border-2 border-dashed border-zinc-300 dark:border-zinc-800 flex items-center justify-center flex-col gap-2 p-4 text-zinc-400 dark:text-zinc-600">
                            <x-flux::icon.users class="w-8 h-8" />
                            <span class="text-sm font-medium text-center">Warte auf Spieler...<br>Code: <strong class="text-zinc-700 dark:text-zinc-300">{{ $game->code }}</strong></span>
                        </div>
                    @elseif($playerCount === 1)
                        <x-seat :player="$orderedPlayers[0]" :current-player-id="$currentPlayer?->id" rotate="0" class="flex-1" />
                    @elseif($playerCount === 2)
                        <!-- 2 Players opposite: Top (180) and Bottom (0) -->
                        <x-seat :player="$orderedPlayers[0]" :current-player-id="$currentPlayer?->id" rotate="180" class="flex-1" />
                        <x-seat :player="$orderedPlayers[1]" :current-player-id="$currentPlayer?->id" rotate="0" class="flex-1" />
                    @elseif($playerCount === 3)
                        <!-- 3 Players: 1 Top (180), 2 Bottom (0) -->
                        <x-seat :player="$orderedPlayers[0]" :current-player-id="$currentPlayer?->id" rotate="180" class="flex-[1]" />
                        <div class="flex-[1] flex gap-2 sm:gap-4">
                            <x-seat :player="$orderedPlayers[1]" :current-player-id="$currentPlayer?->id" rotate="0" class="w-1/2" />
                            <x-seat :player="$orderedPlayers[2]" :current-player-id="$currentPlayer?->id" rotate="0" class="w-1/2" />
                        </div>
                    @elseif($playerCount === 4)
                        <!-- 4 Players: 2 Top (180), 2 Bottom (0) -->
                        <div class="flex-1 flex gap-2 sm:gap-4">
                            <x-seat :player="$orderedPlayers[0]" :current-player-id="$currentPlayer?->id" rotate="180" class="w-1/2" />
                            <x-seat :player="$orderedPlayers[1]" :current-player-id="$currentPlayer?->id" rotate="180" class="w-1/2" />
                        </div>
                        <div class="flex-1 flex gap-2 sm:gap-4">
                            <x-seat :player="$orderedPlayers[2]" :current-player-id="$currentPlayer?->id" rotate="0" class="w-1/2" />
                            <x-seat :player="$orderedPlayers[3]" :current-player-id="$currentPlayer?->id" rotate="0" class="w-1/2" />
                        </div>
                    @elseif($playerCount === 5)
                        <!-- 5 Players: 1 Top(180), 2 Mid(90/-90), 2 Bottom(0) -->
                        <x-seat :player="$orderedPlayers[0]" :current-player-id="$currentPlayer?->id" rotate="180" class="flex-[1.5]" />
                        <div class="flex-[2] flex gap-2 sm:gap-4">
                            <x-seat :player="$orderedPlayers[1]" :current-player-id="$currentPlayer?->id" rotate="90" class="w-1/2" />
                            <x-seat :player="$orderedPlayers[2]" :current-player-id="$currentPlayer?->id" rotate="-90" class="w-1/2" />
                        </div>
                        <div class="flex-[1.5] flex gap-2 sm:gap-4">
                            <x-seat :player="$orderedPlayers[3]" :current-player-id="$currentPlayer?->id" rotate="0" class="w-1/2" />
                            <x-seat :player="$orderedPlayers[4]" :current-player-id="$currentPlayer?->id" rotate="0" class="w-1/2" />
                        </div>
                    @elseif($playerCount >= 6)
                        <!-- 6+ Players: Grid Layout Top(2fr), Mid1(1fr/1fr), Mid2(1fr/1fr), Bottom(2fr) -->
                        <div class="flex-1 grid grid-cols-2 grid-rows-4 gap-2 sm:gap-4">
                            <x-seat :player="$orderedPlayers[0]" :current-player-id="$currentPlayer?->id" rotate="180" class="col-span-2 row-span-1" />
                            
                            <x-seat :player="$orderedPlayers[1]" :current-player-id="$currentPlayer?->id" rotate="90" class="col-span-1 row-span-1" />
                            <x-seat :player="$orderedPlayers[2]" :current-player-id="$currentPlayer?->id" rotate="-90" class="col-span-1 row-span-1" />
                            
                            <x-seat :player="$orderedPlayers[3]" :current-player-id="$currentPlayer?->id" rotate="90" class="col-span-1 row-span-1" />
                            <x-seat :player="$orderedPlayers[4]" :current-player-id="$currentPlayer?->id" rotate="-90" class="col-span-1 row-span-1" />
                            
                            <x-seat :player="$orderedPlayers[5]" :current-player-id="$currentPlayer?->id" rotate="0" class="col-span-2 row-span-1" />
                        </div>
                    @endif
                </div>
            @elseif($viewLayout === 'focused' && $currentPlayer)
                <!-- Focused Layout (Current player large at bottom, others small at top) -->
                <div class="flex-1 w-full h-full flex flex-col gap-2 sm:gap-4 overflow-hidden">
                    @php
                        $others = $orderedPlayers->filter(fn($p) => $p->id !== $currentPlayer->id)->values();
                        $otherCount = $others->count();
                        
                        $gridClass = match($otherCount) {
                            0 => 'grid-cols-1',
                            1 => 'grid-cols-1',
                            2 => 'grid-cols-2',
                            3 => 'grid-cols-2 grid-rows-2',
                            4 => 'grid-cols-2 grid-rows-2',
                            5 => 'grid-cols-3 grid-rows-2',
                            6 => 'grid-cols-3 grid-rows-2',
                            default => 'grid-cols-3',
                        };
                    @endphp
                    
                    <!-- Other Players Area -->
                    <div class="flex-1 min-h-[35vh] bg-zinc-100 dark:bg-zinc-900/30 rounded-[32px] p-2 sm:p-4 overflow-hidden">
                        @if($otherCount === 0)
                            <div class="w-full h-full flex items-center justify-center text-zinc-400 dark:text-zinc-600">
                                <span class="text-sm font-medium">Bisher keine anderen Spieler am Tisch.</span>
                            </div>
                        @else
                            <div class="w-full h-full grid {{ $gridClass }} gap-2 sm:gap-4 items-stretch justify-items-stretch">
                                @foreach($others as $index => $otherp)
                                    @php
                                        // Center the 3rd player if there are 3 opponent cards total
                                        $spanClass = ($otherCount === 3 && $index === 2) ? 'col-span-2' : '';
                                    @endphp
                                    <div class="relative w-full h-full {{ $spanClass }}" style="container-type: size;">
                                        <div class="absolute inset-0">
                                            <livewire:game.player-card :player="$otherp" :is-current-player="false" :key="'other-'.$otherp->id" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    
                    <!-- Focused Current Player Area -->
                    <div class="h-[45vh] w-full shrink-0 relative" style="container-type: size;">
                        <div class="absolute inset-0">
                            <livewire:game.player-card :player="$currentPlayer" :is-current-player="true" :key="'focused-'.$currentPlayer->id" />
                        </div>
                    </div>
                </div>
            @else
                <!-- Normal Phone Grid View -->
                <div class="grid grid-cols-2 gap-2 sm:gap-4 flex-1">
                    @foreach($orderedPlayers as $player)
                        <livewire:game.player-card :player="$player" :is-current-player="$player->id === $currentPlayer?->id" :key="'player-'.$player->id" />
                    @endforeach
                    
                    @if($players->count() < 4)
                        <div class="rounded-[32px] border-2 border-dashed border-zinc-300 dark:border-zinc-800 flex items-center justify-center flex-col gap-2 p-4 text-zinc-400 dark:text-zinc-600">
                            <x-flux::icon.users class="w-8 h-8" />
                            <span class="text-sm font-medium text-center">Warte auf Spieler...<br>Code: <strong class="text-zinc-700 dark:text-zinc-300">{{ $game->code }}</strong></span>
                        </div>
                    @endif
                </div>
            @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
