<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <!-- Set standard dark mode background -->
    <body class="bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-white relative">
        <div class="h-[100svh] w-full overflow-hidden flex flex-col">
            {{ $slot }}
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
