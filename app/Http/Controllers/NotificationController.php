<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // If the notifications table doesn't exist (migrations not run), return an empty list
        if (! Schema::hasTable('notifications')) {
            $notifications = collect();
            return view('notifications.index', compact('notifications'))->with('migrations_missing', true);
        }

        $user = $request->user();

        // Base relation builders
        $allNotifications = $user->notifications();

        // We'll fetch a generous recent set and then dedupe by leave id so the UI
        // shows a single notification per leave (avoids duplicates like ManagerApproved + LeaveStatusChanged).
        $dedupFn = function ($n) {
            $d = (array) $n->data;
            return $d['leave_id'] ?? $d['id'] ?? $n->id;
        };

        $perPage = 20;
        $page = max(1, (int) $request->get('page', 1));

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['administrator', 'admin', 'hr'])) {
            $types = [
                \App\Notifications\NewLeaveForApprover::class,
                \App\Notifications\NewLeaveSubmitted::class,
                \App\Notifications\LeaveFullyApproved::class,
                \App\Notifications\LeaveStatusChanged::class,
                \App\Notifications\SupervisorApproved::class,
            ];

            $items = $allNotifications->whereIn('type', $types)->latest()->take(200)->get();
        } elseif (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['manager', 'supervisor', 'hod'])) {
            // Managers/HOD/Supervisors: show all notifications for their account (not limited by type)
            $items = $allNotifications->latest()->take(200)->get();
        } else {
            // Employee: show approvals relevant to them (supervisor approved, fully approved, or status changed to approved)
            $items = $allNotifications->where(function ($q) {
                $q->where('type', \App\Notifications\SupervisorApproved::class)
                    ->orWhere('type', \App\Notifications\LeaveFullyApproved::class)
                    ->orWhere(function ($q2) {
                        $q2->where('type', \App\Notifications\LeaveStatusChanged::class)
                            ->where('data->status', 'approved');
                    });
            })->latest()->take(200)->get();
        }

        // Dedupe by leave id/key and paginate manually
        $deduped = collect($items)->unique($dedupFn)->values();
        $total = $deduped->count();
        $slice = $deduped->forPage($page, $perPage);
        $notifications = new \Illuminate\Pagination\LengthAwarePaginator($slice, $total, $perPage, $page, [
            'path' => url()->current(),
            'query' => $request->query(),
        ]);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, $id)
    {
        if (! Schema::hasTable('notifications')) {
            return response()->json(['success' => false, 'message' => 'Notifications not configured'], 500);
        }

        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }

    public function markAllRead(Request $request)
    {
        if (! Schema::hasTable('notifications')) {
            return response()->json(['success' => false, 'message' => 'Notifications not configured'], 500);
        }

        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}
