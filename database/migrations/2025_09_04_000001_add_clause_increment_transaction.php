<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('transaction_type')->insert([
            ['id' => 4, 'biwenger_type_name' => 'clauseIncrement', 'name' => 'Subida de cláusula']
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('transaction_type')->where('id', 4)->delete();
    }
};
