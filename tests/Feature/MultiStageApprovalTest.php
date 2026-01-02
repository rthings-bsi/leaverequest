<?php

use App\Models\User;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    // ensure roles exist in test database
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'supervisor']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'manager']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'hr']);
});

it('runs through multi-stage approval flow', function () {
    // create employee
    $employee = User::factory()->create(['department' => 'Engineering']);
    $supervisor = User::factory()->create(['department' => 'Engineering']);
    $manager = User::factory()->create(['department' => 'Engineering']);
    $hr = User::factory()->create();

    // assign roles (ensure roles exist in db in test environment)
    $supervisor->assignRole('supervisor');
    $manager->assignRole('manager');
    $hr->assignRole('hr');

    // employee submits a leave
    $this->actingAs($employee);

    $response = $this->post(route('leave.store'), [
        'leave_type' => 'sick leave',
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'reason' => 'Feeling unwell',
        'department' => 'Engineering',
    ]);

    // in some flows the app redirects to approvals list; accept either
    $response->assertRedirect(route('approvals.index'));

    $leave = LeaveRequest::latest()->first();
    expect($leave)->not->toBeNull();

    // supervisor approves
    $this->actingAs($supervisor);
    $res = $this->post(route('department_approval.approve', $leave->id), ['comment' => 'OK']);
    $res->assertRedirect(route('department_approval.index'));

    $leave->refresh();
    expect($leave->supervisor_approved_at)->not->toBeNull();

    // manager approves
    $this->actingAs($manager);
    $res2 = $this->post(route('department_approval.approve', $leave->id), ['comment' => 'Approved by manager']);
    $res2->assertRedirect(route('department_approval.index'));

    $leave->refresh();
    expect($leave->manager_approved_at)->not->toBeNull();
    expect($leave->final_status)->toBe('approved');

    // HR should receive notification
    Notification::assertSentTo([$hr], \App\Notifications\LeaveFullyApproved::class);
});

it('manager cannot approve before supervisor', function () {
    $employee = User::factory()->create(['department' => 'Engineering']);
    $supervisor = User::factory()->create(['department' => 'Engineering']);
    $manager = User::factory()->create(['department' => 'Engineering']);

    $manager->assignRole('manager');
    $supervisor->assignRole('supervisor');

    $this->actingAs($employee);
    $this->post(route('leave.store'), [
        'leave_type' => 'sick leave',
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'reason' => 'Test',
        'department' => 'Engineering',
    ]);

    $leave = LeaveRequest::latest()->first();

    // Manager attempts to approve before supervisor
    $this->actingAs($manager);
    $res = $this->post(route('department_approval.approve', $leave->id), ['comment' => 'Looks fine']);
    $res->assertRedirect(route('department_approval.index'));
    $res->assertSessionHas('error');
});

it('manager cannot reject before supervisor', function () {
    $employee = User::factory()->create(['department' => 'Engineering']);
    $supervisor = User::factory()->create(['department' => 'Engineering']);
    $manager = User::factory()->create(['department' => 'Engineering']);

    $manager->assignRole('manager');
    $supervisor->assignRole('supervisor');

    $this->actingAs($employee);
    $this->post(route('leave.store'), [
        'leave_type' => 'sick leave',
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'reason' => 'Test',
        'department' => 'Engineering',
    ]);

    $leave = LeaveRequest::latest()->first();

    // Manager attempts to reject before supervisor
    $this->actingAs($manager);
    $res = $this->post(route('department_approval.reject', $leave->id), ['comment' => 'No']);
    $res->assertRedirect(route('department_approval.index'));
    $res->assertSessionHas('error');
});
