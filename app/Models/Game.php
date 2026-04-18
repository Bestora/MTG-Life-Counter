<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'started_at',
        'settings',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'settings' => 'array',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
