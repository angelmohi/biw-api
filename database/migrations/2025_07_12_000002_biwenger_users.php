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
        Schema::create('biwenger_user', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('league_id');

            $table->unsignedBigInteger('biwenger_id')->unique();
            $table->string('name');
            $table->text('icon')->nullable();
            $table->unsignedInteger('position');
            $table->unsignedInteger('points');
            $table->unsignedBigInteger('initial_balance');

            $table->timestamps();

            $table->foreign('league_id')->references('id')->on('league');
        });

        Schema::create('biwenger_user_balance', function (Blueprint $table) {
            $table->increments('id');
            
            $table->unsignedInteger('user_id');

            $table->bigInteger('team_value');
            $table->bigInteger('team_size');
            $table->bigInteger('cash');
            $table->bigInteger('maximum_bid');
            $table->bigInteger('balance');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('biwenger_user');
            
            // Add indexes for efficient daily balance queries
            $table->index(['user_id', 'created_at'], 'idx_user_date');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('biwenger_user_balance');
        Schema::drop('biwenger_user');
    }
};
