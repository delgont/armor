<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('permission_groups')) {
            Schema::create('permission_groups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->string('registrar')->nullable(); // class containing permissions for specific group
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('permission_group_id')->nullable();
                $table->timestamps();

                $table->foreign('permission_group_id')->references('id')->on('permission_groups')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('role_groups')) {
            Schema::create('role_groups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('role_group_id')->nullable();
                $table->timestamps();
                $table->foreign('role_group_id')->references('id')->on('role_groups')->onDelete('set null');

            });
        }

        if (!Schema::hasTable('model_has_permissions') && Schema::hasTable('permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();

                $table->index(['model_type', 'model_id'], 'model_has_roles_model_id_model_type_index');

                $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

                $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        if (!Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();

                $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

                $table->primary(['role_id', 'model_id', 'model_type'],
                'model_has_roles_role_model_type_primary');
            });
        }
       
        if (!Schema::hasTable('role_has_permissions') && Schema::hasTable('roles')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');
                $table->timestamps();

                $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

                $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

                $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
            });
        }
       
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission_groups');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('permission_groups');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
    }
}
