<?php
use App\Livewire\Game\Lobby;
use App\Models\Game;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders successfully', function () {
    Livewire::test(Lobby::class)
        ->assertStatus(200);
});

it('can create a new game via lobby', function () {
    Livewire::test(Lobby::class)
        ->call('createGame')
        ->assertRedirect();
        
    expect(Game::count())->toBe(1);
});

it('can join an existing game', function () {
    $game = Game::create(['code' => 'ABCDE', 'name' => 'MTG Match']);

    Livewire::test(Lobby::class)
        ->set('joinCode', 'abcde')
        ->call('joinGame')
        ->assertRedirect(route('game.table', ['code' => 'ABCDE']));
});

it('shows validation error for invalid game code', function () {
    Livewire::test(Lobby::class)
        ->set('joinCode', 'INVALID')
        ->call('joinGame')
        ->assertHasErrors(['joinCode']);
});
