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
        if (!Schema::hasTable('page_accesses')) {
            Schema::create('page_accesses', function (Blueprint $table) {
                $table->id();
                $table->string('page_name')->unique();
                $table->string('page_url');
                $table->ipAddress('ip');
                $table->integer('count')->default(0);
                $table->string('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_accesses');
    }
};
