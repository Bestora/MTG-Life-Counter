<?php
use App\Livewire\Game\PlayerCard;
use App\Models\Game;
use App\Models\Player;
use Livewire\Livewire;
use App\Events\PlayerLifeUpdated;
use App\Events\CommanderDamageUpdated;
use App\Events\CounterUpdated;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function() {
    $this->game = Game::create(['code' => 'TESTX', 'settings' => ['starting_life' => 40]]);
    $this->player1 = Player::create(['game_id' => $this->game->id, 'name' => 'Alice', 'life' => 40]);
    $this->player2 = Player::create(['game_id' => $this->game->id, 'name' => 'Bob', 'life' => 40]);
});

it('can update life and broadcast', function () {
    Event::fake([PlayerLifeUpdated::class]);

    Livewire::test(PlayerCard::class, ['player' => $this->player1, 'isCurrentPlayer' => true])
        ->call('updateLife', -5);
        
    expect($this->player1->fresh()->life)->toBe(35);
    
    Event::assertDispatched(PlayerLifeUpdated::class, function ($event) {
        return $event->player->id === $this->player1->id;
    });
});

it('can update commander damage and reduce life', function () {
    Event::fake([CommanderDamageUpdated::class, PlayerLifeUpdated::class]);

    // Alice takes 2 damage from Bob's commander
    Livewire::test(PlayerCard::class, ['player' => $this->player1, 'isCurrentPlayer' => true])
        ->call('updateCommanderDamage', $this->player2->id, 2, false);
        
    expect($this->player1->fresh()->life)->toBe(38); // 40 - 2
    
    Event::assertDispatched(CommanderDamageUpdated::class, function ($event) {
        return $event->damage === 2 && $event->sourcePlayerId === $this->player2->id;
    });
});

it('can update custom counters and clamps below 0', function () {
    Event::fake([CounterUpdated::class]);

    Livewire::test(PlayerCard::class, ['player' => $this->player1, 'isCurrentPlayer' => true])
        ->call('updateCounter', 'Poison', 1);
        
    Event::assertDispatched(CounterUpdated::class, function ($event) {
        return $event->type === 'Poison' && $event->value === 1;
    });
    
    // Test clamping at 0
    Livewire::test(PlayerCard::class, ['player' => $this->player1, 'isCurrentPlayer' => true])
        ->call('updateCounter', 'Poison', -5);
        
    Event::assertDispatched(CounterUpdated::class, function ($event) {
        return $event->type === 'Poison' && $event->value === 0;
    });
});

it('can eliminate a player', function () {
    Event::fake([PlayerLifeUpdated::class]);

    Livewire::test(PlayerCard::class, ['player' => $this->player1, 'isCurrentPlayer' => true])
        ->call('toggleElimination');
        
    expect($this->player1->fresh()->is_eliminated)->toBeTrue();
    
    Event::assertDispatched(PlayerLifeUpdated::class);
});
