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
        if ($this->indexExists('page_accesses', 'page_accesses_ip_unique')) {
            Schema::table('page_accesses', function (Blueprint $table) {
                $table->dropUnique('page_accesses_ip_unique'); // Drop the unique constraint
            });
        }
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

     /**
     * Check if the specified index exists on the table.
     *
     * @param string $tableName
     * @param string $indexName
     * @return bool
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        $result = DB::select("SHOW INDEX FROM {$tableName} WHERE Key_name = ?", [$indexName]);
        return count($result) > 0;
    }
};
