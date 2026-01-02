<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\Department;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $departments = Department::orderBy('name')->get();

        return view('profile.edit', [
            'user' => $request->user(),
            'departments' => $departments,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validated();

        // handle avatar upload
        if ($request->hasFile('avatar')) {
            // remove old avatar if exists
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_path'] = $path;
        }

        // fill allowed fields
        // If a department_id is provided, store both the id and the department name
        $departmentId = $data['department_id'] ?? null;
        $departmentName = $user->department;
        if ($departmentId) {
            $dept = Department::find($departmentId);
            if ($dept) {
                $departmentName = $dept->name;
            }
        } elseif (array_key_exists('department', $data)) {
            // fallback to legacy department string field if provided
            $departmentName = $data['department'];
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            // keep legacy `department` string for compatibility
            'department' => $departmentName,
            'department_id' => $departmentId,
            'nip' => $data['nip'] ?? $user->nip,
            'contract_start_date' => $data['contract_start_date'] ?? $user->contract_start_date,
            'contract_end_date' => $data['contract_end_date'] ?? $user->contract_end_date,
        ]);

        if (isset($data['avatar_path'])) {
            $user->avatar_path = $data['avatar_path'];
        }

        $user->save();

        // If contract_start_date is set or updated, optionally reset leave quota metadata
        // We don't persist a quota counter here; quota is computed dynamically per-year.
        // This hook is left intentionally minimal to avoid side effects.

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
