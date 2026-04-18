<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommanderDamage extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'source_player_id',
        'damage',
        'partner_damage',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function sourcePlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'source_player_id');
    }
}
