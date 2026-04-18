<?php
use App\Livewire\Game\Table;
use App\Models\Game;
use App\Models\Player;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function() {
    $this->game = Game::create(['code' => 'TEST1', 'settings' => ['starting_life' => 40]]);
});

it('requires joining a game if no player in session', function () {
    Livewire::test(Table::class, ['code' => $this->game->code])
        ->assertSee('Join Game: TEST1')
        ->assertSee('Enter Game');
});

it('can join as player', function () {
    Livewire::test(Table::class, ['code' => $this->game->code])
        ->set('playerName', 'Urza')
        ->set('playerColor', '#ef4444')
        ->call('joinAsPlayer')
        ->assertSee('Urza');
        
    expect(Player::count())->toBe(1);
    expect(session('player_id_'.$this->game->id))->not->toBeNull();
});

it('throws model not found exception for invalid game code', function () {
    Livewire::test(Table::class, ['code' => 'INVALID']);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
