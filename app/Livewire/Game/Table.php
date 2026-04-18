<?php
namespace App\Livewire\Game;

use App\Models\Game;
use App\Models\Player;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

#[Layout('layouts.game')]
class Table extends Component
{
    public Game $game;
    public ?Player $currentPlayer = null;
    public bool $isSpectator = false;
    
    // Player Setup Form
    public $playerName = '';
    public $playerColor = '#ef4444';
    
    #[Session]
    public string $viewLayout = '6-rect'; // Default smart layout // grid | 6-rect
    
    #[Session]
    public int $boardRotation = 0; // 0, 90, 180, 270
    
    public function mount($code)
    {
        $this->game = Game::where('code', strtoupper($code))->firstOrFail();
        
        $playerId = session('player_id_'.$this->game->id);
        if ($playerId) {
            $this->currentPlayer = Player::find($playerId);
        }
    }
    
    public function joinAsPlayer()
    {
        $this->validate([
            'playerName' => 'required|min:1|max:20',
            'playerColor' => 'required|string',
        ]);

        $settings = $this->game->settings ?? [];
        $startingLife = $settings['starting_life'] ?? 40;

        $player = Player::create([
            'game_id' => $this->game->id,
            'name' => $this->playerName,
            'color' => $this->playerColor,
            'life' => $startingLife,
        ]);
        
        // Ensure new player is added to the order array
        $order = $settings['player_order'] ?? $this->game->players->pluck('id')->toArray();
        if (!in_array($player->id, $order)) {
            $order[] = $player->id;
            $settings['player_order'] = array_values($order);
            $this->game->settings = $settings;
            $this->game->save();
        }

        session(['player_id_'.$this->game->id => $player->id]);
        $this->currentPlayer = $player;
        
        $event = new \App\Events\PlayerJoined($this->game->id, $player->id);
        
        $socketId = request()->header('X-Socket-Id');
        if ($socketId && $socketId !== 'undefined') {
            broadcast($event)->toOthers();
        } else {
            broadcast($event);
        }
        
        $this->js('window.location.reload()');
    }
    
    public function joinAsExisting($playerId)
    {
        $player = $this->game->players()->find($playerId);
        if ($player) {
            session(['player_id_'.$this->game->id => $player->id]);
            $this->currentPlayer = $player;
            $this->isSpectator = false;
            
            // Optionally reload to ensure perfect hydration
            $this->js('window.location.reload()');
        }
    }
    
    #[On('echo:game.{game.id},PlayerJoined')]
    public function onPlayerJoined($event)
    {
        // This empty method just triggers a Livewire component re-render so that
        // $this->game->players()->get() inside render() picks up the new player.
    }
    
    #[On('refresh-board')]
    public function refreshTableData()
    {
        $this->game->refresh();
    }
    
    #[On('echo:game.{game.id},GameSettingsUpdated')]
    public function onGameSettingsUpdated($event)
    {
        $this->game->settings = $event['settings'];
    }

    public function swapPlayers($playerId1, $playerId2)
    {
        $settings = $this->game->settings ?? [];
        $order = $settings['player_order'] ?? $this->game->players->pluck('id')->toArray();
        
        $idx1 = array_search($playerId1, $order);
        $idx2 = array_search($playerId2, $order);
        
        if ($idx1 === false || $idx2 === false) {
            $order = $this->game->players->pluck('id')->toArray();
            $idx1 = array_search($playerId1, $order);
            $idx2 = array_search($playerId2, $order);
        }
        
        if ($idx1 !== false && $idx2 !== false) {
            $temp = $order[$idx1];
            $order[$idx1] = $order[$idx2];
            $order[$idx2] = $temp;
            
            $settings['player_order'] = array_values($order);
            $this->game->settings = $settings;
            $this->game->save();
            
            $event = new \App\Events\GameSettingsUpdated($this->game->id, $settings);
            
            $socketId = request()->header('X-Socket-Id');
            if ($socketId && $socketId !== 'undefined') {
                broadcast($event)->toOthers();
            } else {
                broadcast($event);
            }
        }
    }

    public function render()
    {
        return view('livewire.game.table', [
            'players' => $this->game->players()->orderBy('created_at')->get(),
        ]);
    }
}
