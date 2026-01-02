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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'nip')) {
                    $table->string('nip')->nullable()->after('department');
                }
                if (! Schema::hasColumn('users', 'avatar_path')) {
                    $table->string('avatar_path')->nullable()->after('nip');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'avatar_path')) {
                    $table->dropColumn('avatar_path');
                }
                if (Schema::hasColumn('users', 'nip')) {
                    $table->dropColumn('nip');
                }
            });
        }
    }
};
