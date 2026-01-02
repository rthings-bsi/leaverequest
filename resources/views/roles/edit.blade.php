@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
    <div class="max-w-3xl mx-auto p-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h1 class="text-2xl font-semibold mb-4">Edit Role</h1>

            @if ($errors->any())
                <div class="mb-4 text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('roles.update', $role->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" value="{{ old('name', $role->name) }}" required
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Guard Name</label>
                        <input type="text" name="guard_name" value="{{ old('guard_name', $role->guard_name) }}"
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Update Role</button>
                        <a href="{{ route('roles.index') }}" class="ml-4 text-gray-600">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
