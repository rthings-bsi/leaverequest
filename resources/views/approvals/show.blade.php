@extends('layouts.app')

@section('title', 'Leave Details (Approvals)')

@section('content')
    {{-- Moved from resources/views/leave/show.blade.php to approvals.show --}}
    @includeIf('leave.__detail_contents', ['leave' => $leave, 'approvals' => $approvals ?? null])
@endsection
