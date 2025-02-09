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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Rename 'changes' column to 'after' if it exists
            if (Schema::hasColumn('audit_logs', 'changes')) {
                $table->renameColumn('changes', 'after');
            }

            // Add 'before' column if it does not exist
            if (!Schema::hasColumn('audit_logs', 'before')) {
                $table->json('before')->nullable()->after('after');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Rename 'after' back to 'changes' if it exists
            if (Schema::hasColumn('audit_logs', 'after')) {
                $table->renameColumn('after', 'changes');
            }

            // Drop 'before' column if it exists
            if (Schema::hasColumn('audit_logs', 'before')) {
                $table->dropColumn('before');
            }
        });
    }
};
