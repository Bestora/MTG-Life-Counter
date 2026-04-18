<?php
namespace App\Livewire\Game;

use App\Models\Player;
use App\Models\CommanderDamage;
use App\Models\Counter;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class PlayerCard extends Component
{
    public Player $player;
    public bool $isCurrentPlayer = false;
    
    public int $life;
    public array $cmdDamage = [];
    public array $counters = [];

    public function mount(Player $player, bool $isCurrentPlayer)
    {
        $this->player = $player;
        $this->isCurrentPlayer = $isCurrentPlayer;
        $this->life = $player->life;
        
        $this->loadCommanderDamages();
        $this->loadCounters();
    }
    
    public function loadCommanderDamages()
    {
        $this->cmdDamage = [];
        $damages = CommanderDamage::where('player_id', $this->player->id)->get();
        foreach ($damages as $d) {
            $this->cmdDamage[$d->source_player_id] = [
                'damage' => $d->damage,
                'partner_damage' => $d->partner_damage,
            ];
        }
    }
    
    public function loadCounters()
    {
        $this->counters = [];
        $dbCounters = Counter::where('player_id', $this->player->id)->get();
        foreach ($dbCounters as $c) {
            $this->counters[$c->type] = $c->value;
        }
    }

    #[Computed]
    public function otherPlayers()
    {
        return Player::where('game_id', $this->player->game_id)
            ->where('id', '!=', $this->player->id)
            ->get();
    }

    #[On('echo:game.{player.game_id},PlayerLifeUpdated')]
    public function onLifeUpdated($event)
    {
        if ($event['player']['id'] === $this->player->id) {
            $this->life = $event['player']['life'];
            $this->player->is_eliminated = $event['player']['is_eliminated'];
            
            // Notify frontend to vibrate if this is our player taking damage from someone else
            $this->dispatch('vibrate-phone', playerId: $this->player->id);
        }
    }
    
    #[On('refresh-board')]
    public function refreshData()
    {
        $this->player->refresh();
        $this->life = $this->player->life;
        $this->loadCommanderDamages();
        $this->loadCounters();
    }

    #[On('echo:game.{player.game_id},CommanderDamageUpdated')]
    public function onCommanderDamageUpdated($event)
    {
        if ($event['playerId'] === $this->player->id) {
            $this->cmdDamage[$event['sourcePlayerId']] = [
                'damage' => $event['damage'],
                'partner_damage' => $event['partnerDamage'],
            ];
        }
    }
    
    #[On('echo:game.{player.game_id},CounterUpdated')]
    public function onCounterUpdated($event)
    {
        if ($event['playerId'] === $this->player->id) {
            $this->counters[$event['type']] = $event['value'];
        }
    }

    public function updateLife($amount, $broadcast = true)
    {
        $this->life += $amount;
        
        $this->player->life = $this->life;
        $this->player->save();
        
        if ($broadcast) {
            $this->broadcastPlayerState();
        }
    }
    
    public function toggleElimination()
    {
        $this->player->is_eliminated = !$this->player->is_eliminated;
        $this->player->save();
        
        $this->broadcastPlayerState();
    }
    
    public function updateCounter($type, $amount)
    {
        $counter = Counter::firstOrCreate(
            ['player_id' => $this->player->id, 'type' => $type],
            ['value' => 0]
        );
        
        $counter->value += $amount;
        if ($counter->value < 0) {
            $counter->value = 0;
        }
        $counter->save();
        
        $this->counters[$type] = $counter->value;
        
        $event = new \App\Events\CounterUpdated(
            $this->player->game_id, 
            $this->player->id, 
            $type, 
            $counter->value
        );
        
        $socketId = request()->header('X-Socket-Id');
        if ($socketId && $socketId !== 'undefined') {
            broadcast($event)->toOthers();
        } else {
            broadcast($event);
        }
    }
    
    public function updateCommanderDamage($sourceId, $amount, $isPartner = false)
    {
        $damageRec = CommanderDamage::firstOrCreate(
            ['player_id' => $this->player->id, 'source_player_id' => $sourceId],
            ['damage' => 0, 'partner_damage' => 0]
        );
        
        if ($isPartner) {
            $damageRec->partner_damage += $amount;
        } else {
            $damageRec->damage += $amount;
        }
        $damageRec->save();
        
        $this->cmdDamage[$sourceId] = [
            'damage' => $damageRec->damage,
            'partner_damage' => $damageRec->partner_damage,
        ];
        
        $this->updateLife(-$amount, false); 
        $this->broadcastPlayerState();
        
        $event = new \App\Events\CommanderDamageUpdated(
            $this->player->game_id, 
            $this->player->id, 
            $sourceId, 
            $damageRec->damage, 
            $damageRec->partner_damage
        );
        
        $socketId = request()->header('X-Socket-Id');
        if ($socketId && $socketId !== 'undefined') {
            broadcast($event)->toOthers();
        } else {
            broadcast($event);
        }
    }

    private function broadcastPlayerState()
    {
        $event = new \App\Events\PlayerLifeUpdated($this->player);
        
        $socketId = request()->header('X-Socket-Id');
        if ($socketId && $socketId !== 'undefined') {
            broadcast($event)->toOthers();
        } else {
            broadcast($event);
        }
    }

    public function render()
    {
        return view('livewire.game.player-card');
    }
}
