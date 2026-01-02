<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MasterEmployeeController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(25);

        // Precompute approvers per user for the view (role+department based)
        $approvers = [];
        foreach ($users as $user) {
            // prefer explicit primary approvers if set
            $list = [];
            try {
                if (!empty($user->primary_supervisor_id) || !empty($user->primary_manager_id)) {
                    if (!empty($user->primary_supervisor_id)) {
                        $sup = User::find($user->primary_supervisor_id);
                        $list['Supervisor'] = $sup ? [$sup->name] : [];
                    }
                    if (!empty($user->primary_manager_id)) {
                        $mgr = User::find($user->primary_manager_id);
                        $list['Manager'] = $mgr ? [$mgr->name] : [];
                    }
                } else {
                    $deptName = is_string($user->department) ? $user->department : ($user->department?->name ?? null);

                    $resolveByRole = function ($role) use ($deptName) {
                        try {
                            return User::role($role)->where('department', $deptName)->get()->pluck('name')->filter()->values()->all();
                        } catch (\Throwable $e) {
                            return User::where('department', $deptName)->get()->pluck('name')->filter()->values()->all();
                        }
                    };

                    if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['manager'])) {
                        $list['HOD'] = $resolveByRole('hod');
                    } elseif (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['supervisor'])) {
                        $list['Manager'] = $resolveByRole('manager');
                    } else {
                        $list['Supervisor'] = $resolveByRole('supervisor');
                        $list['Manager'] = $resolveByRole('manager');
                    }
                }
            } catch (\Throwable $e) {
                $list = [];
            }

            $approvers[$user->id] = $list;
        }

        // also compute a simple boolean: does this user require only HOD approval?
        $hodOnly = [];
        foreach ($approvers as $uid => $list) {
            $hodOnly[$uid] = (is_array($list) && count($list) === 1 && array_key_exists('HOD', $list));
        }

        return view('master.employee.index', compact('users', 'approvers', 'hodOnly'));
    }

    public function create()
    {
        // Roles will be loaded in the view via Spatie Role model if needed
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        // candidate approvers: supervisors and managers (global list)
        $supervisors = \App\Models\User::role('supervisor')->orderBy('name')->get();
        $managers = \App\Models\User::role('manager')->orderBy('name')->get();

        return view('master.employee.create', compact('roles', 'departments', 'supervisors', 'managers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'department_id' => 'nullable|integer|exists:departments,id',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
            'approver_roles' => 'nullable|array',
            'approver_roles.*' => 'string',
            'primary_supervisor_id' => 'nullable|integer|exists:users,id',
            'primary_manager_id' => 'nullable|integer|exists:users,id',
        ]);

        $departmentName = null;
        if (!empty($data['department_id'])) {
            $dept = \App\Models\Department::find($data['department_id']);
            $departmentName = $dept ? $dept->name : null;
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'department' => $departmentName,
            'department_id' => $data['department_id'] ?? null,
            'primary_supervisor_id' => $data['primary_supervisor_id'] ?? null,
            'primary_manager_id' => $data['primary_manager_id'] ?? null,
            'approver_roles' => $data['approver_roles'] ?? null,
            'contract_start_date' => $data['contract_start_date'] ?? null,
            'contract_end_date' => $data['contract_end_date'] ?? null,
        ]);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return redirect()->route('master.employees.index')->with('success', 'Employee created.');
    }

    public function edit(User $employee)
    {
        // Route model binding uses the parameter name {employee} for this resource
        $user = $employee;
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        // approver candidates
        $supervisors = \App\Models\User::role('supervisor')->orderBy('name')->get();
        $managers = \App\Models\User::role('manager')->orderBy('name')->get();
        return view('master.employee.edit', compact('user', 'roles', 'departments', 'supervisors', 'managers'));
    }

    public function update(Request $request, User $employee)
    {
        $user = $employee;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'department_id' => 'nullable|integer|exists:departments,id',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
            'approver_roles' => 'nullable|array',
            'approver_roles.*' => 'string',
            'primary_supervisor_id' => 'nullable|integer|exists:users,id',
            'primary_manager_id' => 'nullable|integer|exists:users,id',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['department_id'])) {
            $dept = \App\Models\Department::find($data['department_id']);
            $user->department = $dept ? $dept->name : null;
            $user->department_id = $data['department_id'];
        } else {
            $user->department = null;
            $user->department_id = null;
        }
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        // save explicit approvers
        $user->primary_supervisor_id = $data['primary_supervisor_id'] ?? null;
        $user->primary_manager_id = $data['primary_manager_id'] ?? null;
        $user->approver_roles = $data['approver_roles'] ?? null;
        $user->contract_start_date = $data['contract_start_date'] ?? null;
        $user->contract_end_date = $data['contract_end_date'] ?? null;
        $user->save();

        $user->syncRoles($data['roles'] ?? []);

        return redirect()->route('master.employees.index')->with('success', 'Employee updated.');
    }

    public function destroy(User $employee)
    {
        $employee->delete();
        return redirect()->route('master.employees.index')->with('success', 'Employee deleted.');
    }
}
