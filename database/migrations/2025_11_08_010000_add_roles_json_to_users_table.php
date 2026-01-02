<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('users', 'roles')) {
            Schema::table('users', function (Blueprint $table) {
                // JSON column to store array of role names for quick access
                $table->json('roles')->nullable()->after('department_id');
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
        if (Schema::hasColumn('users', 'roles')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('roles');
            });
        }
    }
};
