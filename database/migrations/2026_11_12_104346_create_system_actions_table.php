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
        if (Schema::hasTable('system_actions')) {
            return;
        }

        Schema::create('system_actions', function (Blueprint $table) {

            $table->id();

            $table->uuid()->unique();

            $table->string('action');

            $table->text('description')
                ->nullable();

            $table->integer('order')->nullable();

            $table->enum('risk_level', [
                'low',
                'medium',
                'high',
                'critical',
            ])->default('low');

            $table->boolean('supports_undo')
                ->default(false);

            $table->boolean('otp_protected')
                ->default(false);

            $table->unsignedBigInteger(
                'system_action_group_id'
            );

            $table->timestamps();

            $table->unique([
                'system_action_group_id',
                'action',
            ]);

            $table->index('action');

            $table->foreign(
                'system_action_group_id'
            )
            ->references('id')
            ->on('system_action_groups')
            ->cascadeOnDelete();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_actions');
    }
};
