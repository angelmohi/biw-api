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
        Schema::create('league', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedBigInteger('biwenger_id')->unique();
            $table->string('name');
            $table->text('bearer_user');
            $table->text('bearer_league');
            $table->text('bearer_token');
            $table->date('start_date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('league');
    }
};
