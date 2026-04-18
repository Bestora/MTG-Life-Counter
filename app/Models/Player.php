<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'name',
        'color',
        'background_url',
        'life',
        'is_eliminated',
        'defeat_message',
    ];

    protected $casts = [
        'is_eliminated' => 'boolean',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function commanderDamages(): HasMany
    {
        return $this->hasMany(CommanderDamage::class, 'player_id');
    }

    public function counters(): HasMany
    {
        return $this->hasMany(Counter::class);
    }
}
