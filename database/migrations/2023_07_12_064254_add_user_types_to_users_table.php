<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserTypesToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('multiauth.add_usertype_to_users_model')) {
            Schema::table('users', function (Blueprint $table) {
                $table->nullableMorphs('user');
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
        if (config('multiauth.add_usertype_to_users_model')) {
            Schema::table('users', function (Blueprint $table) {
                //
            });
        }
        
    }
}
