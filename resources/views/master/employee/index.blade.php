@extends('layouts.app')

@section('title', 'Master Employees')

@section('content')
    <div class="max-w-7xl mx-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-extrabold tracking-tight">Master Employees</h1>
            <a href="{{ route('master.employees.create') }}"
                class="inline-flex items-center gap-3 px-6 py-3 bg-indigo-600 text-white rounded-full shadow-lg text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 5c.552 0 1 .448 1 1v5h5c.552 0 1 .448 1 1s-.448 1-1 1h-5v5c0 .552-.448 1-1 1s-1-.448-1-1v-5H6c-.552 0-1-.448-1-1s.448-1 1-1h5V6c0-.552.448-1 1-1z" />
                </svg>
                <span class="font-medium">New Employee</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100">
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm align-middle">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="py-3 px-3">Name</th>
                                <th class="py-3 px-3">Email</th>
                                <th class="py-3 px-3">Approvers</th>
                                <th class="py-3 px-3">HOD only</th>
                                <th class="py-3 px-3">Roles</th>
                                <th class="py-3 px-3">Department</th>
                                <th class="py-3 px-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($users as $user)
                                <tr>
                                    <td class="py-3 px-3 font-medium">{{ $user->name }}</td>
                                    <td class="py-3 px-3 text-gray-600">{{ $user->email }}</td>
                                    <td class="py-3 px-3 text-sm text-gray-700">
                                        @php
                                            $userApprovers = $approvers[$user->id] ?? [];
                                        @endphp

                                        @if (empty($userApprovers))
                                            <span class="text-gray-400">—</span>
                                        @else
                                            @foreach ($userApprovers as $roleLabel => $names)
                                                <div class="mb-1">
                                                    <span
                                                        class="text-xs font-semibold text-slate-600">{{ $roleLabel }}:</span>
                                                    <span
                                                        class="text-xs text-slate-700 ml-2">{{ count($names) ? implode(', ', $names) : '—' }}</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        @php $isHodOnly = $hodOnly[$user->id] ?? false; @endphp
                                        @if ($isHodOnly)
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded text-xs bg-amber-100 text-amber-800">Yes</span>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        @foreach ($user->getRoleNames() as $r)
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">{{ $r }}</span>
                                        @endforeach
                                    </td>
                                    <td class="py-3 px-3 text-gray-600">{{ $user->department ?? '—' }}</td>
                                    <td class="py-3 px-3">
                                        <a href="{{ route('master.employees.edit', $user->id) }}"
                                            class="text-indigo-600 hover:underline">Edit</a>
                                        <span class="mx-2 text-gray-300">|</span>
                                        <form method="POST" action="{{ route('master.employees.destroy', $user->id) }}"
                                            class="inline-block" onsubmit="return confirm('Delete this employee?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 hover:underline bg-transparent border-0 p-0">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-gray-500">No employees found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
