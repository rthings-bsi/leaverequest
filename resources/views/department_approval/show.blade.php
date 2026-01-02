@extends('layouts.app')

@section('title', 'Review Leave')

@section('content')
    <main class="max-w-3xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Review Leave Request</h1>

        <div class="p-4 border rounded-md space-y-3">
            <div><strong>Employee:</strong> {{ $leave->user?->name }}</div>
            <div><strong>NIP:</strong> {{ $leave->nip }}</div>
            <div><strong>Department:</strong> {{ $leave->department }}</div>
            <div><strong>Type:</strong> {{ $leave->leave_type }}</div>
            <div><strong>Period:</strong> {{ $leave->start_date }} — {{ $leave->end_date }}</div>
            <div><strong>Mandatory document:</strong> {{ $leave->mandatory_document }}</div>
            @if ($leave->attachment_path)
                <div><a href="{{ asset('storage/' . $leave->attachment_path) }}" target="_blank">Download attachment</a></div>
            @endif
        </div>

        <form action="{{ route('department_approval.approve', $leave->id) }}" method="POST" class="mt-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold mb-2">Comment (optional)</label>
                <textarea name="comment" rows="4" class="w-full rounded-md bg-gray-100 p-3"></textarea>
            </div>

            <div class="mt-4 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Approve</button>
            </div>
        </form>

        <form action="{{ route('department_approval.reject', $leave->id) }}" method="POST" class="mt-2">
            @csrf
            <div>
                <label class="block text-sm font-semibold mb-2">Rejection reason (optional)</label>
                <textarea name="comment" rows="3" class="w-full rounded-md bg-gray-100 p-3"></textarea>
            </div>
            <div class="mt-2">
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded">Reject</button>
            </div>
        </form>
    </main>
@endsection
