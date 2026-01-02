<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'roles_cache')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('roles_cache')->nullable()->after('department_id');
            });
        }

        // Copy data from roles -> roles_cache if roles column exists
        if (Schema::hasColumn('users', 'roles')) {
            DB::statement('UPDATE users SET roles_cache = roles WHERE roles IS NOT NULL');

            // drop the conflicting 'roles' column to avoid colliding with Spatie relation
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'roles')) {
                    $table->dropColumn('roles');
                }
            });
        }
    }

    public function down()
    {
        // try to restore 'roles' from roles_cache (best-effort)
        if (! Schema::hasColumn('users', 'roles')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('roles')->nullable()->after('department_id');
            });
        }

        if (Schema::hasColumn('users', 'roles_cache')) {
            DB::statement('UPDATE users SET roles = roles_cache WHERE roles_cache IS NOT NULL');
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'roles_cache')) {
                    $table->dropColumn('roles_cache');
                }
            });
        }
    }
};
