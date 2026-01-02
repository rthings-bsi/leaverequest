<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\MasterEmployeeController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    // Always redirect the application root to the login page.
    return redirect()->route('login');
});

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/today', [\App\Http\Controllers\DashboardController::class, 'today']);
    Route::get('/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'stats']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Leave request routes (detail view moved to approvals)
    // We no longer serve a standalone leave index page — redirect `/leave` to the Approvals menu.
    Route::resource('leave', LeaveRequestController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);

    // Redirect the legacy index path to the approvals list (admin/HR/approvers handle leaves there)
    Route::get('leave', function () {
        return redirect()->route('approvals.index');
    })->name('leave.index');

    Route::get('leave/{leave}', function (\App\Models\LeaveRequest $leave) {
        return redirect()->route('approvals.show', $leave->id);
    })->name('leave.show');
    Route::get('leave/{leave}/download', [\App\Http\Controllers\LeaveRequestController::class, 'download'])->name('leave.download');
    // Preview stored attachment (serve inline so browser shows PDF/image instead of forcing download)
    Route::get('leave/{leave}/attachment', [\App\Http\Controllers\LeaveRequestController::class, 'previewAttachment'])->name('leave.attachment.preview');
    // Internal preview page that wraps the PDF/attachment in the app layout
    Route::get('leave/{leave}/preview', [\App\Http\Controllers\LeaveRequestController::class, 'previewPage'])->name('leave.preview');
    // Approval routes for admin/HR
    Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('approvals/export', [ApprovalController::class, 'export'])->name('approvals.export');
    Route::get('approvals/{leave}', [ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('approvals/{leave}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('approvals/{leave}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');

    // Department-level approval (supervisor/manager)
    Route::get('department-approval', [\App\Http\Controllers\DepartmentApprovalController::class, 'index'])->name('department_approval.index');
    Route::get('department-approval/export', [\App\Http\Controllers\DepartmentApprovalController::class, 'export'])->name('department_approval.export');
    Route::get('department-approval/{leave}', [\App\Http\Controllers\DepartmentApprovalController::class, 'show'])->name('department_approval.show');
    Route::post('department-approval/{leave}/approve', [\App\Http\Controllers\DepartmentApprovalController::class, 'approve'])->name('department_approval.approve');
    Route::post('department-approval/{leave}/reject', [\App\Http\Controllers\DepartmentApprovalController::class, 'reject'])->name('department_approval.reject');

    // Master employee (HR/Admin)
    Route::resource('master/employees', MasterEmployeeController::class)->names('master.employees');

    // Role management (Admin)
    Route::resource('roles', RoleManagementController::class)->names('roles');

    // Notifications (all authenticated users)
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    // Server time endpoint for client clock synchronization
    Route::get('server-time', [\App\Http\Controllers\ServerTimeController::class, 'now'])->name('server.time');

    // Debug helper: return computed dashboard stats for the logged-in user.
    // Use this URL while logged-in to inspect what the backend computes for the cards.
    Route::get('debug/dashboard-stats', function () {
        $user = Auth::user();
        if (! $user) return response()->json(['error' => 'unauthenticated'], 401);

        $query = \App\Models\LeaveRequest::with('user')->orderBy('created_at', 'desc');
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'hr'])) {
            // no restriction
        } elseif (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['manager', 'supervisor'])) {
            if ($user->department) $query->where('department', $user->department);
        } else {
            $query->where('user_id', $user->id);
        }

        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $accepted = (clone $query)->whereIn('status', ['approved', 'accept', 'accepted'])->count();
        $rejected = (clone $query)->whereIn('status', ['rejected', 'denied', 'deny'])->count();
        $today = (clone $query)->whereDate('start_date', \Carbon\Carbon::today()->toDateString())->count();
        $employees = (clone $query)->pluck('user_id')->filter()->unique()->count();
        $departments = (clone $query)->pluck('department')->filter()->unique()->count();

        return response()->json(compact('total', 'today', 'pending', 'accepted', 'rejected', 'employees', 'departments'));
    })->name('debug.dashboard.stats');
});

require __DIR__ . '/auth.php';

// Lightweight HR assistant endpoint (placeholder) used by the dashboard chat UI.
Route::post('assistant/hr', function (Request $request) {
    // Simple placeholder reply. Replace with real assistant integration as needed.
    return response()->json(['reply' => 'HR Assistant is not configured yet.']);
})->name('assistant.hr')->middleware('auth');
