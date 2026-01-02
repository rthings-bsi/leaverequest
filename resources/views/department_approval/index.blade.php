@extends('layouts.app')

@section('title', 'Approvals')

@section('content')
    <main class="max-w-4xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Department Approvals</h1>

        @if (auth()->user() &&
                auth()->user()->hasAnyRole(['administrator', 'admin', 'hr']))
            <div class="mb-4 flex items-center gap-3">
                @if (auth()->user()->hasAnyRole(['administrator', 'admin', 'hr']))
                    {{-- HR/Admin: export approvals using ApprovalsExport with optional department filter --}}
                    <form method="GET" action="{{ route('approvals.export') }}" class="inline-flex items-center gap-2">
                        <input type="hidden" name="q" value="{{ request('q') }}">
                        <input type="hidden" name="scope" value="{{ request('scope') }}">
                        @if (isset($departments) && $departments)
                            <label for="department" class="sr-only">Department</label>
                            <select id="department" name="department"
                                class="rounded-full border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm">
                                <option value="">All departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->name }}"
                                        {{ request('department') == $dept->name ? 'selected' : '' }}>{{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="department" value="{{ auth()->user()->department }}">
                        @endif
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-full text-sm shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-200">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Export (HR/Admin)
                        </button>
                    </form>
                @endif
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 text-green-700">{{ session('success') }}</div>
        @endif

        @if ($leaves->isEmpty())
            <div class="text-gray-600">No leave requests found.</div>
        @else
            <ul class="space-y-3">
                @foreach ($leaves as $leave)
                    <li class="p-4 border rounded-md">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold">{{ $leave->user?->name ?? 'Unknown' }}</div>
                                <div class="text-sm text-gray-600">{{ $leave->leave_type }} — {{ $leave->start_date }} to
                                    {{ $leave->end_date }}</div>
                            </div>
                            <div>
                                <a href="{{ route('department_approval.show', $leave->id) }}"
                                    class="text-blue-600">Review</a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">
                {{ $leaves->links() }}
            </div>
        @endif
    </main>
@endsection
