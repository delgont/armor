<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('permission_groups', 'parent_id')) {
            return;
        }

        Schema::table('permission_groups', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('permission_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('permission_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};