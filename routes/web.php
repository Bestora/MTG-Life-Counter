<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Game\Lobby;
use App\Livewire\Game\Table;

Route::get('/', Lobby::class)->name('home');
Route::get('/game/{code}', Table::class)->name('game.table');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
