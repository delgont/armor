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
        if (Schema::hasTable('system_action_groups')) {
            return;
        }

        Schema::create('system_action_groups', function (Blueprint $table) {

                $table->id();

                $table->uuid()->unique();

                $table->string('key');
                
                $table->string('name');

                $table->string('registrar');

                $table->timestamps();

                $table->unique(['key', 'registrar'], 'uniq_system_action_groups');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_action_groups');
    }
};
