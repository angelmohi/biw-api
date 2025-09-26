<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_price_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('biwenger_player_id')->index();
            $table->string('player_name');
            $table->string('slug')->nullable()->index();
            $table->bigInteger('price');
            $table->bigInteger('price_increment')->nullable();
            $table->date('record_date')->index();
            $table->timestamps();
            
            $table->unique(['biwenger_player_id', 'record_date'], 'player_date_unique');
            
            $table->index(['record_date', 'price']);
            $table->index(['player_name']);
            $table->index(['price_increment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_price_history');
    }
};
