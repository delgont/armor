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
        Schema::table('page_accesses', function (Blueprint $table) {
            $table->dropUnique('page_accesses_ip_unique'); // Drop the unique constraint
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_accesses', function (Blueprint $table) {
            $table->unique('ip'); // Re-add the unique constraint
        });
    }
};
