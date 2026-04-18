<div class="flex items-center justify-center min-h-[80vh] p-4">
    <flux:card class="w-full max-w-md space-y-6">
        <div class="text-center">
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">MTG Life Counter</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2">Real-time device sync for Commander</p>
        </div>

        <div class="space-y-4 pt-4">
            <flux:button wire:click="createGame" variant="primary" class="w-full h-12 text-lg">
                Create New Game
            </flux:button>

            <div class="relative py-2">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-zinc-50 dark:bg-zinc-900 px-2 text-sm text-zinc-500">or</span>
                </div>
            </div>

            <form wire:submit="joinGame" class="space-y-3">
                <flux:input 
                    wire:model="joinCode" 
                    label="Join Code" 
                    placeholder="e.g. X7H9B" 
                    class="text-center text-xl uppercase tracking-widest"
                />
                <flux:button type="submit" class="w-full h-12 text-lg">
                    Join Game
                </flux:button>
            </form>
        </div>
    </flux:card>
</div>
