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
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('leave_requests', 'nip')) {
                    $table->string('nip')->nullable()->after('admin_comment');
                }
                if (!Schema::hasColumn('leave_requests', 'department')) {
                    $table->string('department')->nullable()->after('nip');
                }
                if (!Schema::hasColumn('leave_requests', 'mandatory_document')) {
                    $table->string('mandatory_document')->nullable()->after('department');
                }
                if (!Schema::hasColumn('leave_requests', 'period')) {
                    $table->string('period')->nullable()->after('mandatory_document');
                }
                if (!Schema::hasColumn('leave_requests', 'cover_by')) {
                    $table->string('cover_by')->nullable()->after('period');
                }
                if (!Schema::hasColumn('leave_requests', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('cover_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (Schema::hasColumn('leave_requests', 'attachment_path')) {
                    $table->dropColumn('attachment_path');
                }
                if (Schema::hasColumn('leave_requests', 'cover_by')) {
                    $table->dropColumn('cover_by');
                }
                if (Schema::hasColumn('leave_requests', 'period')) {
                    $table->dropColumn('period');
                }
                if (Schema::hasColumn('leave_requests', 'mandatory_document')) {
                    $table->dropColumn('mandatory_document');
                }
                if (Schema::hasColumn('leave_requests', 'department')) {
                    $table->dropColumn('department');
                }
                if (Schema::hasColumn('leave_requests', 'nip')) {
                    $table->dropColumn('nip');
                }
            });
        }
    }
};
