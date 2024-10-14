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
        Schema::table('users', function (Blueprint $table) {
            // Check if the column 'password_changed_at' doesn't exist before adding it
            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable();
            }

            // Check if the column 'suspended' doesn't exist before adding it
            if (!Schema::hasColumn('users', 'suspended')) {
                $table->boolean('suspended')->default(false);
            }

            // Check if the column 'suspended_till' doesn't exist before adding it
            if (!Schema::hasColumn('users', 'suspended_till')) {
                $table->timestamp('suspended_till')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Optionally, you can drop the columns if needed during rollback
            if (Schema::hasColumn('users', 'password_changed_at')) {
                $table->dropColumn('password_changed_at');
            }

            if (Schema::hasColumn('users', 'suspended')) {
                $table->dropColumn('suspended');
            }

            if (Schema::hasColumn('users', 'suspended_till')) {
                $table->dropColumn('suspended_till');
            }
        });
    }
};
