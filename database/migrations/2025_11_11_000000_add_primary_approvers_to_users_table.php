<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryApproversToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('primary_supervisor_id')->nullable()->after('department_id');
            $table->unsignedBigInteger('primary_manager_id')->nullable()->after('primary_supervisor_id');

            // add foreign keys if users table uses bigIncrements
            $table->foreign('primary_supervisor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('primary_manager_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_supervisor_id']);
            $table->dropForeign(['primary_manager_id']);
            $table->dropColumn(['primary_supervisor_id', 'primary_manager_id']);
        });
    }
}
