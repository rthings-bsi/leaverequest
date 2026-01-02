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
                if (!Schema::hasColumn('leave_requests', 'supervisor_id')) {
                    $table->unsignedBigInteger('supervisor_id')->nullable()->after('attachment_path');
                }
                if (!Schema::hasColumn('leave_requests', 'supervisor_approved_at')) {
                    $table->timestamp('supervisor_approved_at')->nullable()->after('supervisor_id');
                }
                if (!Schema::hasColumn('leave_requests', 'supervisor_comment')) {
                    $table->text('supervisor_comment')->nullable()->after('supervisor_approved_at');
                }
                if (!Schema::hasColumn('leave_requests', 'manager_id')) {
                    $table->unsignedBigInteger('manager_id')->nullable()->after('supervisor_comment');
                }
                if (!Schema::hasColumn('leave_requests', 'manager_approved_at')) {
                    $table->timestamp('manager_approved_at')->nullable()->after('manager_id');
                }
                if (!Schema::hasColumn('leave_requests', 'manager_comment')) {
                    $table->text('manager_comment')->nullable()->after('manager_approved_at');
                }
                if (!Schema::hasColumn('leave_requests', 'final_status')) {
                    $table->string('final_status')->default('pending')->after('manager_comment');
                }
                if (!Schema::hasColumn('leave_requests', 'hr_notified_at')) {
                    $table->timestamp('hr_notified_at')->nullable()->after('final_status');
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
                $cols = [
                    'hr_notified_at',
                    'final_status',
                    'manager_comment',
                    'manager_approved_at',
                    'manager_id',
                    'supervisor_comment',
                    'supervisor_approved_at',
                    'supervisor_id',
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('leave_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
