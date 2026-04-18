<div x-data="{ 
        localLife: $wire.entangle('life'),
        timer: null,
        firedLong: false,
        customAction: 'set',
        customAmount: '',
        openCustomModal(action) {
            this.customAction = action;
            this.customAmount = '';
            $dispatch('modal-show', { name: 'custom-life-{{$player->id}}' });
            
            // Immediate focus for robust mobile keyboard spawning
            setTimeout(() => {
                let input = document.getElementById('custom-input-{{$player->id}}');
                if (input) input.focus();
            }, 50);
        }
     }"
    @vibrate-phone.window="if ($event.detail.playerId === {{ $player->id }} && {{ $isCurrentPlayer ? 'true' : 'false' }}) { if ('vibrate' in navigator) navigator.vibrate([150, 50, 150]); }"
    class="rounded-2xl p-4 flex flex-col items-center justify-center shadow-md relative overflow-hidden h-full transition-opacity duration-500"
    style="background-color: {{ $player->color }}15; border: 2px solid {{ $player->color }}50; {{ $player->is_eliminated ? 'filter: grayscale(100%); opacity: 0.6;' : '' }}">

    <!-- Top Left: Menu & Name -->
    <div class="absolute top-2 lg:top-4 left-3 flex items-center gap-2">
        <flux:dropdown>
            <flux:button size="sm" variant="subtle" icon="ellipsis-horizontal"
                class="!px-1 !py-1 h-8 w-8 text-zinc-500" />
            <flux:menu>
                <flux:menu.item wire:click="toggleElimination"
                    icon="{{ $player->is_eliminated ? 'heart' : 'no-symbol' }}">
                    {{ $player->is_eliminated ? 'Revive Player' : 'Eliminate Player' }}
                </flux:menu.item>

                <flux:menu.separator />

                <flux:menu.submenu heading="Sitzplatz tauschen" icon="arrows-up-down">
                    @foreach($this->otherPlayers as $other)
                        <flux:menu.item wire:click="$parent.swapPlayers({{ $player->id }}, {{ $other->id }})">
                            Mit {{ $other->name }} tauschen
                        </flux:menu.item>
                    @endforeach
                </flux:menu.submenu>

                <flux:menu.separator />

                <flux:menu.item @click="openCustomModal('set')" icon="pencil">
                    Leben setzen (HP)
                </flux:menu.item>

                <flux:menu.item wire:click="" icon="paint-brush" class="opacity-50">
                    Change Color (Coming soon)
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        <span class="font-bold text-zinc-800 dark:text-zinc-200 {{ $player->is_eliminated ? 'line-through' : '' }}">
            {{ $player->name }}
        </span>
    </div>

    <!-- Top Right: Modals Trigger -->
    <div class="absolute top-2 lg:top-4 right-3 flex items-center gap-1 sm:gap-2">
        @if($isCurrentPlayer)
            <flux:badge size="sm" class="bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 hidden sm:flex">You
            </flux:badge>
        @endif

        <flux:modal.trigger name="counters-{{ $player->id }}">
            <flux:button size="sm" variant="subtle" icon="beaker"
                class="h-8 w-8 !p-1 text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200" />
        </flux:modal.trigger>

        <flux:modal.trigger name="commander-damage-{{ $player->id }}">
            <flux:button size="sm" variant="subtle" icon="shield-exclamation"
                class="h-8 w-8 !p-1 text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200" />
        </flux:modal.trigger>
    </div>

    <!-- Central Life Total with Alpine logic -->
    <div
        class="text-6xl sm:text-8xl font-black text-zinc-900 dark:text-white tabular-nums select-none tracking-tighter">
        <span x-text="localLife"></span>
    </div>

    <!-- Bottom Plus/Minus Controls -->
    <div class="absolute bottom-2 lg:bottom-4 flex gap-4 w-full px-4 justify-between">
        <flux:button
            @pointerdown="firedLong = false; timer = setTimeout(() => { firedLong = true; if ('vibrate' in navigator) navigator.vibrate(70); }, 400)"
            @pointerup="clearTimeout(timer); if(firedLong) { openCustomModal('sub'); }"
            @pointerleave="clearTimeout(timer); if(firedLong) { openCustomModal('sub'); firedLong = false; }"
            @pointerout="clearTimeout(timer);"
            @touchcancel="clearTimeout(timer); if(firedLong) { openCustomModal('sub'); firedLong = false; }"
            @contextmenu.prevent
            @click="if(!firedLong) { $wire.updateLife(-1); if ({{ $isCurrentPlayer ? 'true' : 'false' }} && 'vibrate' in navigator) navigator.vibrate(40); } else { firedLong = false; }"
            icon="minus" variant="subtle"
            class="w-16 h-16 rounded-full bg-zinc-900/10 dark:bg-white/10 hover:bg-zinc-900/20 dark:hover:bg-white/20 select-none" />

        <flux:button
            @pointerdown="firedLong = false; timer = setTimeout(() => { firedLong = true; if ('vibrate' in navigator) navigator.vibrate(70); }, 400)"
            @pointerup="clearTimeout(timer); if(firedLong) { openCustomModal('add'); }"
            @pointerleave="clearTimeout(timer); if(firedLong) { openCustomModal('add'); firedLong = false; }"
            @pointerout="clearTimeout(timer);"
            @touchcancel="clearTimeout(timer); if(firedLong) { openCustomModal('add'); firedLong = false; }"
            @contextmenu.prevent
            @click="if(!firedLong) { $wire.updateLife(1); if ({{ $isCurrentPlayer ? 'true' : 'false' }} && 'vibrate' in navigator) navigator.vibrate(40); } else { firedLong = false; }"
            icon="plus" variant="subtle"
            class="w-16 h-16 rounded-full bg-zinc-900/10 dark:bg-white/10 hover:bg-zinc-900/20 dark:hover:bg-white/20 select-none" />
    </div>

    <!-- Custom HP Adjustment Modal -->
    <flux:modal name="custom-life-{{ $player->id }}" class="md:w-96">
        <div class="space-y-4">
            <h2 class="text-lg font-bold"
                x-text="customAction === 'add' ? 'Leben hinzufügen' : (customAction === 'sub' ? 'Leben abziehen' : 'Leben setzen (auf exakten Wert)')">
                Leben anpassen</h2>

            <flux:input id="custom-input-{{$player->id}}" type="number" x-model="customAmount" inputmode="numeric"
                pattern="[0-9]*" placeholder="0"
                @keydown.enter="$wire.applyCustomAmount(customAction, customAmount); customAmount = ''; $dispatch('modal-close', { name: 'custom-life-{{$player->id}}' });" />

            <div class="flex gap-2">
                <flux:modal.close class="flex-1">
                    <flux:button variant="ghost" class="w-full">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" class="flex-1"
                    @click="$wire.applyCustomAmount(customAction, customAmount); customAmount = ''; $dispatch('modal-close', { name: 'custom-life-{{$player->id}}' });"
                    x-bind:disabled="!customAmount">Bestätigen</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- If Eliminated Message -->
    @if($player->is_eliminated)
        <div class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
            <span
                class="bg-zinc-900/80 text-white font-bold text-xl px-4 py-2 rounded-lg rotate-[-12deg] uppercase tracking-widest shadow-xl">
                Defeated
            </span>
        </div>
    @endif

    <!-- Commander Damage Modal -->
    <flux:modal name="commander-damage-{{ $player->id }}" class="md:w-96" position="bottom">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-bold">Commander Damage</h2>
                <p class="text-sm text-zinc-500">Damage dealt to {{ $player->name }} by others</p>
            </div>

            <div class="space-y-4 max-h-[50vh] overflow-y-auto pb-4 px-1">
                @if($this->otherPlayers->isEmpty())
                    <p class="text-sm text-zinc-500 italic">No other players in the game.</p>
                @endif

                @foreach($this->otherPlayers as $other)
                    @php
                        $dmg = $cmdDamage[$other->id]['damage'] ?? 0;
                        $partnerDmg = $cmdDamage[$other->id]['partner_damage'] ?? 0;
                    @endphp
                    <div class="flex flex-col gap-3 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800"
                        style="border-left: 4px solid {{ $other->color }}">

                        <!-- Main Commander -->
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $other->color }}"></div>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $other->name }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:button wire:click="updateCommanderDamage({{ $other->id }}, -1, false)" icon="minus"
                                    size="sm" variant="subtle" class="h-8 w-8 rounded-full" />
                                <span class="text-xl font-bold w-8 text-center tabular-nums">{{ $dmg }}</span>
                                <flux:button wire:click="updateCommanderDamage({{ $other->id }}, 1, false)" icon="plus"
                                    size="sm" variant="subtle" class="h-8 w-8 rounded-full" />
                            </div>
                        </div>

                        <!-- Partner Commander -->
                        <div
                            class="flex justify-between items-center opacity-75 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <span class="text-sm pl-5">Partner</span>
                            <div class="flex items-center gap-2">
                                <flux:button wire:click="updateCommanderDamage({{ $other->id }}, -1, true)" icon="minus"
                                    size="sm" variant="subtle" class="h-7 w-7 rounded-full" />
                                <span class="text-lg font-bold w-8 text-center tabular-nums">{{ $partnerDmg }}</span>
                                <flux:button wire:click="updateCommanderDamage({{ $other->id }}, 1, true)" icon="plus"
                                    size="sm" variant="subtle" class="h-7 w-7 rounded-full" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex">
                <flux:modal.close>
                    <flux:button variant="ghost" class="w-full">Done</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <!-- Counters Modal -->
    <flux:modal name="counters-{{ $player->id }}" class="md:w-96" position="bottom">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-bold">Player Counters</h2>
                <p class="text-sm text-zinc-500">Track special counters for {{ $player->name }}</p>
            </div>

            <div class="space-y-4 max-h-[50vh] overflow-y-auto pb-4 px-1">
                @php
                    $counterTypes = ['Poison', 'Energy', 'Experience', 'Storm', 'Commander Tax'];
                @endphp

                @foreach($counterTypes as $type)
                    @php
                        $val = $counters[$type] ?? 0;
                    @endphp
                    <div
                        class="flex justify-between items-center p-3 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $type }}</span>

                        <div class="flex items-center gap-2">
                            <flux:button wire:click="updateCounter('{{ $type }}', -1)" icon="minus" size="sm"
                                variant="subtle" class="h-8 w-8 rounded-full" />
                            <span class="text-xl font-bold w-8 text-center tabular-nums">{{ $val }}</span>
                            <flux:button wire:click="updateCounter('{{ $type }}', 1)" icon="plus" size="sm" variant="subtle"
                                class="h-8 w-8 rounded-full" />
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex">
                <flux:modal.close>
                    <flux:button variant="ghost" class="w-full">Done</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>