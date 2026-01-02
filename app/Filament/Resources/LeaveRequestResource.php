<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use App\Models\User;
use App\Models\Approval;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()
                ->required()
                ->label('Requester'),
            Forms\Components\Select::make('leave_type')
                ->options([
                    'annual' => 'Annual Leave',
                    'sick' => 'Sick Leave',
                    'other' => 'Other Leave',
                ])->required(),
            Forms\Components\DatePicker::make('start_date')->required(),
            Forms\Components\DatePicker::make('end_date')->required(),
            Forms\Components\TextInput::make('days')->numeric(),
            Forms\Components\Textarea::make('reason'),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])->disabled(),
            Forms\Components\Textarea::make('admin_comment')
                ->label('Admin Comment'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Requester')->searchable(),
                Tables\Columns\TextColumn::make('leave_type')->label('Type'),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->date(),
                Tables\Columns\BadgeColumn::make('status')
                    ->enum([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('supervisor_approve')
                    ->label('Supervisor Approve')
                    ->requiresConfirmation()
                    ->visible(fn($record) => auth()->user()?->hasRole('supervisor') && is_null($record->supervisor_approved_at) && auth()->user()?->department === $record->department)
                    ->form([
                        Forms\Components\Textarea::make('admin_comment')->label('Comment (optional)'),
                    ])
                    ->action(function (LeaveRequest $record, array $data = []): void {
                        $record->approveBySupervisor(auth()->user(), $data['admin_comment'] ?? null);
                    })
                    ->color('primary'),

                Tables\Actions\Action::make('manager_approve')
                    ->label('Manager Approve')
                    ->requiresConfirmation()
                    ->visible(fn($record) => auth()->user()?->hasRole('manager') && !is_null($record->supervisor_approved_at) && is_null($record->manager_approved_at) && auth()->user()?->department === $record->department)
                    ->form([
                        Forms\Components\Textarea::make('admin_comment')->label('Comment (optional)'),
                    ])
                    ->action(function (LeaveRequest $record, array $data = []): void {
                        $record->approveByManager(auth()->user(), $data['admin_comment'] ?? null);
                    })
                    ->color('success'),

                Tables\Actions\Action::make('hod_approve')
                    ->label('Approve (HOD)')
                    ->requiresConfirmation()
                    // Visible to department HODs and admins (admins can trigger HOD approval)
                    ->visible(fn($record) => (
                        (auth()->user()?->hasRole('hod') && auth()->user()?->department === $record->department)
                        || auth()->user()?->hasAnyRole(['administrator', 'admin'])
                    ))
                    ->form([
                        Forms\Components\Textarea::make('admin_comment')->label('Comment (optional)'),
                        // Only HODs should see the 'Approve as' choice; admins trigger HOD approval automatically
                        Forms\Components\Select::make('approver_stage')
                            ->label('Approve as')
                            ->options([
                                'hod' => 'HOD',
                                'manager' => 'Manager',
                            ])
                            ->default('hod')
                            ->required()
                            ->visible(fn() => auth()->user()?->hasRole('hod')),
                    ])
                    ->action(function (LeaveRequest $record, array $data = []): void {
                        // If current user is HOD in the department, attribute to them. Otherwise (admin),
                        // auto-assign the department HOD as the approver.
                        $current = auth()->user();
                        if ($current && $current->hasRole('hod') && $current->department === $record->department) {
                            $approver = $current;
                        } else {
                            // find department HOD; fallback to any HOD; finally fallback to current user
                            try {
                                $approver = User::role('hod')->where('department', $record->department)->first()
                                    ?? User::role('hod')->first()
                                    ?? $current;
                            } catch (\Throwable $e) {
                                $approver = $current;
                            }
                        }

                        $stage = $data['approver_stage'] ?? 'hod';
                        // Admin-triggered approvals should be recorded as HOD approvals
                        if (! ($current && $current->hasRole('hod'))) {
                            $stage = 'hod';
                        }

                        // create approval audit record (stage reflects chosen or forced stage)
                        $approval = Approval::create([
                            'leave_request_id' => $record->id,
                            'approver_id' => $approver?->id,
                            'action' => 'approved',
                            'comment' => $data['admin_comment'] ?? null,
                            'stage' => $stage,
                        ]);

                        // perform manager-level approval using the approver (HOD acts as manager)
                        try {
                            $record->approveByManager($approver ?? $current, $data['admin_comment'] ?? null);
                        } catch (\Throwable $e) {
                            logger()->error('HOD approve action failed: ' . $e->getMessage());
                        }

                        // broadcast approval created for realtime UI
                        try {
                            event(new \App\Events\ApprovalCreated($approval));
                        } catch (\Throwable $e) {
                            // non-fatal
                        }
                    })
                    ->color('secondary'),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_comment')
                            ->label('Comment (optional)'),
                    ])
                    ->action(function (LeaveRequest $record, array $data = []): void {
                        $record->approve(auth()->user(), $data['admin_comment'] ?? null);
                    })
                    ->color('success'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_comment')
                            ->label('Comment (optional)')
                            ->required(),
                    ])
                    ->action(function (LeaveRequest $record, array $data = []): void {
                        $record->reject(auth()->user(), $data['admin_comment'] ?? null);
                    })
                    ->color('danger'),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulkApprove')
                    ->label('Approve Selected')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_comment')
                            ->label('Comment (optional)'),
                    ])
                    ->action(function ($records, array $data = []): void {
                        foreach ($records as $record) {
                            $record->approve(auth()->user(), $data['admin_comment'] ?? null);
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    /**
     * Modify the base query for the Filament table so supervisor-only users
     * do not see leave requests created by manager-role users.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if (! $user) {
            return $query;
        }

        try {
            $isSupervisorOnly = false;
            if (method_exists($user, 'hasAnyRole')) {
                $isSupervisorOnly = $user->hasAnyRole(['supervisor']) && ! $user->hasAnyRole(['manager', 'hod', 'admin', 'hr']);
            } else {
                $roleNames = collect($user->getRoleNames() ?? [])->map(fn($r) => strtolower((string) $r));
                $isSupervisorOnly = $roleNames->contains(fn($r) => str_contains($r, 'supervisor')) && ! $roleNames->contains(fn($r) => str_contains($r, 'manager') || str_contains($r, 'hod') || str_contains($r, 'head') || str_contains($r, 'kepala'));
            }

            if ($isSupervisorOnly) {
                $query->whereHas('user', function ($q) {
                    $q->whereDoesntHave('roles', function ($r) {
                        $r->where('name', 'like', '%manager%');
                    });
                });
            }
        } catch (\Throwable $e) {
            // ignore and return the unmodified query
        }

        return $query;
    }
}
