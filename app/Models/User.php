<?php

namespace App\Models;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $department_id
 * @property int|null $primary_supervisor_id
 * @property int|null $primary_manager_id
 * @property array|null $approver_roles
 *
 * @method bool hasRole(string|array $roles)
 * @method bool hasAnyRole(array|string $roles)
 * @method \Illuminate\Support\Collection getRoleNames()
 * @method static \Illuminate\Database\Eloquent\Builder role(string|array $role)
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department',
        'department_id',
        'primary_supervisor_id',
        'primary_manager_id',
        'approver_roles',
        'nip',
        'avatar_path',
        'contract_start_date',
        'contract_end_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'approver_roles' => 'array',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
        ];
    }

    /**
     * User has many leave requests.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * User belongs to a department.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Primary supervisor relation (optional explicit assignment).
     */
    public function primarySupervisor()
    {
        return $this->belongsTo(User::class, 'primary_supervisor_id');
    }

    /**
     * Primary manager relation (optional explicit assignment).
     */
    public function primaryManager()
    {
        return $this->belongsTo(User::class, 'primary_manager_id');
    }

    /**
     * Get the current leave cycle window based on contract_start_date.
     * If contract_start_date is null, use calendar year.
     */
    public function leaveCycleRange(): array
    {
        $today = Carbon::today();
        $start = $this->contract_start_date
            ? Carbon::parse($this->contract_start_date)->setYear($today->year)
            : $today->copy()->startOfYear();

        if ($start->gt($today)) {
            $start->subYear();
        }

        $end = $start->copy()->addYear()->subDay();

        if ($this->contract_end_date) {
            $contractEnd = Carbon::parse($this->contract_end_date)->endOfDay();

            // If contract already ended before this cycle window, cap both start and end at contract end
            if ($contractEnd->lt($start)) {
                $start = $contractEnd->copy()->startOfDay();
                $end = $contractEnd;
            } elseif ($contractEnd->lt($end)) {
                $end = $contractEnd;
            }
        }

        return [$start, $end];
    }

    /**
     * Sum approved leave days in the current cycle.
     */
    public function usedLeaveDaysInCurrentCycle(): int
    {
        [$cycleStart, $cycleEnd] = $this->leaveCycleRange();

        try {
            return (int) LeaveRequest::where('user_id', $this->id)
                ->whereBetween('start_date', [$cycleStart->toDateString(), $cycleEnd->toDateString()])
                ->where(function ($q) {
                    $q->whereIn('final_status', ['approved'])
                        ->orWhereIn('status', ['approved', 'accept', 'accepted']);
                })
                ->sum('days');
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
