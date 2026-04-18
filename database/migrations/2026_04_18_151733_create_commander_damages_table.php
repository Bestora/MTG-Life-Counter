<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commander_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('source_player_id')->constrained('players')->cascadeOnDelete();
            $table->integer('damage')->default(0);
            $table->integer('partner_damage')->default(0);
            $table->timestamps();
            
            $table->unique(['player_id', 'source_player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commander_damages');
    }
};
