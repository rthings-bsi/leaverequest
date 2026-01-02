@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto p-6">
        <h2 class="text-2xl font-semibold mb-4">Request Details #{{ $leave->id }}</h2>

        <div class="mb-4">
            <div><strong>Name:</strong> {{ $leave->user?->name }}</div>
            <div><strong>Type:</strong> {{ $leave->type }}</div>
            <div><strong>Dates:</strong> {{ $leave->start_date?->format('Y-m-d') }} to
                {{ $leave->end_date?->format('Y-m-d') }}</div>
            <div><strong>Reason:</strong> {{ $leave->reason }}</div>
        </div>

        <form method="POST" action="{{ route('manager.leaves.approve', ['id' => $leave->id]) }}"
            class="inline-block ajax-action" onsubmit="return confirm('Approve this request?')">
            @csrf
            <button class="jeda-approve text-white px-4 py-2 rounded mr-2">Approve</button>
        </form>

        <form method="POST" action="{{ route('manager.leaves.reject', ['id' => $leave->id]) }}"
            class="inline-block ajax-action" onsubmit="return confirm('Reject this request?')">
            @csrf
            <button class="bg-red-500 text-white px-4 py-2 rounded">Reject</button>
        </form>
    </div>
@endsection
