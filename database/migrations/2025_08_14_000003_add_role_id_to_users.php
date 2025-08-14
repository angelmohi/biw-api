<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('email_verified_at');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->index('role_id');
        });

        // Assign staff role to existing users by default
        $staffRoleId = DB::table('roles')->where('name', 'staff')->value('id');
        if ($staffRoleId) {
            DB::table('users')->whereNull('role_id')->update(['role_id' => $staffRoleId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
