@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
    <div class="max-w-7xl mx-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-extrabold tracking-tight">Role Management</h1>
            <a href="{{ route('roles.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Role
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100">
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm align-middle">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="py-3 px-3">Name</th>
                                <th class="py-3 px-3">Guard</th>
                                <th class="py-3 px-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($roles as $role)
                                <tr>
                                    <td class="py-3 px-3 font-medium">{{ $role->name }}</td>
                                    <td class="py-3 px-3 text-gray-600">{{ $role->guard_name }}</td>
                                    <td class="py-3 px-3">
                                        <a href="{{ route('roles.edit', $role->id) }}"
                                            class="text-indigo-600 hover:underline">Edit</a>
                                        <span class="mx-2 text-gray-300">|</span>
                                        <form method="POST" action="{{ route('roles.destroy', $role->id) }}"
                                            class="inline-block" onsubmit="return confirm('Delete this role?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 hover:underline bg-transparent border-0 p-0">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-gray-500">No roles found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
