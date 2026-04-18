<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // e.g. poison, energy, experience
            $table->integer('value')->default(0);
            $table->timestamps();
            
            $table->unique(['player_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
