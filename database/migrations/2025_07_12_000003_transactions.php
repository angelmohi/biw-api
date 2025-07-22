<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_type', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('biwenger_type_name')->unique();
            $table->string('name');
        });

        DB::table('transaction_type')->insert([
            ['id' => 1, 'biwenger_type_name' => 'transfer', 'name' => 'Fichajes'],
            ['id' => 2, 'biwenger_type_name' => 'market', 'name' => 'Mercado de fichajes'],
            ['id' => 3, 'biwenger_type_name' => 'roundFinished', 'name' => 'Fin de jornada']
        ]);

        Schema::create('transaction', function (Blueprint $table) {
            $table->increments('id');

            $table->string('transaction_hash', 64)->unique();
            $table->unsignedInteger('type_id');
            $table->string('description')->nullable();
            $table->bigInteger('amount');
            $table->bigInteger('player_id')->nullable();
            $table->string('player_name')->nullable();
            $table->unsignedInteger('from_user_id')->nullable();
            $table->unsignedInteger('to_user_id')->nullable();
            $table->timestamp('date');

            $table->timestamps();

            $table->index('transaction_hash');
            $table->foreign('type_id')->references('id')->on('transaction_type');
            $table->foreign('from_user_id')->references('id')->on('biwenger_user');
            $table->foreign('to_user_id')->references('id')->on('biwenger_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('transaction');
        Schema::drop('transaction_type');
    }
};
