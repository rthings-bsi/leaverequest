@extends('layouts.app')

@section('title', 'New Employee')

@section('content')
    <div class="max-w-3xl mx-auto p-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h1 class="text-2xl font-semibold mb-4">New Employee</h1>

            @if ($errors->any())
                <div class="mb-4 text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('master.employees.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" required
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <select name="department_id"
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Not set --</option>
                            @foreach ($departments ?? collect() as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contract Start Date</label>
                        <input type="date" name="contract_start_date" value="{{ old('contract_start_date') }}"
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        <p class="text-xs text-gray-500 mt-1">Set when a new contract begins so the leave cycle follows the
                            anniversary.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contract End Date</label>
                        <input type="date" name="contract_end_date" value="{{ old('contract_end_date') }}"
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        <p class="text-xs text-gray-500 mt-1">Optional. Caps leave usage until the contract ends.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Roles</label>
                        <select name="roles[]" multiple class="mt-1 block w-full rounded-md border-gray-200 shadow-sm">
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple roles.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Approver Roles (which roles should approve
                            this employee's leaves)</label>
                        <select name="approver_roles[]" multiple
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm">
                            @foreach (['supervisor', 'manager', 'hod'] as $r)
                                <option value="{{ $r }}"
                                    {{ in_array($r, old('approver_roles', [])) ? 'selected' : '' }}>{{ ucfirst($r) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple approver roles.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Primary Supervisor (optional)</label>
                        <select name="primary_supervisor_id" class="mt-1 block w-full rounded-md border-gray-200 shadow-sm">
                            <option value="">-- Not set -- (no approver)</option>
                            @foreach ($supervisors as $sup)
                                <option value="{{ $sup->id }}"
                                    {{ old('primary_supervisor_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}
                                    ({{ $sup->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Primary Manager (optional)</label>
                        <select name="primary_manager_id" class="mt-1 block w-full rounded-md border-gray-200 shadow-sm">
                            <option value="">-- Not set -- (no approver)</option>
                            @foreach ($managers as $mgr)
                                <option value="{{ $mgr->id }}"
                                    {{ old('primary_manager_id') == $mgr->id ? 'selected' : '' }}>{{ $mgr->name }}
                                    ({{ $mgr->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Create Employee</button>
                        <a href="{{ route('master.employees.index') }}" class="ml-4 text-gray-600">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
