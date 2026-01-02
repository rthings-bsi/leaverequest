<?php

use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\NewLeaveForApprover;
use Illuminate\Support\Facades\Notification;

it('stores_an_unread_notification_for_manager', function () {
    $employee = User::factory()->create();
    $manager = User::factory()->create();

    // create a minimal leave request
    $leave = LeaveRequest::create([
        'user_id' => $employee->id,
        'leave_type' => 'annual',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'days' => 1,
        'reason' => 'Test leave',
        'department' => 'Test Dept',
    ]);

    // send notification synchronously so it is written to the notifications table
    Notification::sendNow($manager, new NewLeaveForApprover($leave));

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $manager->id,
        'type' => NewLeaveForApprover::class,
        'read_at' => null,
    ]);
});

it('limits_recent_notifications_to_six_in_query', function () {
    $employee = User::factory()->create();
    $manager = User::factory()->create();

    // create 8 notifications
    for ($i = 0; $i < 8; $i++) {
        $leave = LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => now()->addDays($i)->toDateString(),
            'end_date' => now()->addDays($i + 1)->toDateString(),
            'days' => 1,
            'reason' => 'Test leave ' . $i,
            'department' => 'Test Dept',
        ]);

        Notification::sendNow($manager, new NewLeaveForApprover($leave));
    }

    $count = $manager->notifications()->latest()->take(6)->get()->count();
    expect($count)->toBe(6);
});
