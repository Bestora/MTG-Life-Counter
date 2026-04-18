<?php
namespace App\Livewire\Game;

use App\Models\Game;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Str;

#[Layout('layouts.game')]
class Lobby extends Component
{
    public $joinCode = '';
    
    public function createGame()
    {
        $code = strtoupper(Str::random(5));
        
        Game::create([
            'code' => $code,
            'name' => 'MTG Table',
            'started_at' => now(),
            'settings' => ['starting_life' => 40],
        ]);
        
        return $this->redirectRoute('game.table', ['code' => $code], navigate: true);
    }
    
    public function joinGame()
    {
        $this->validate([
            'joinCode' => 'required|string',
        ]);
        
        $game = Game::where('code', strtoupper($this->joinCode))->first();
        
        if (!$game) {
            $this->addError('joinCode', 'Game not found. Please check the code.');
            return;
        }
        
        return $this->redirectRoute('game.table', ['code' => $game->code], navigate: true);
    }

    public function render()
    {
        return view('livewire.game.lobby');
    }
}
