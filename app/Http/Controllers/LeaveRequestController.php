<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter');
        $query = LeaveRequest::query()->latest();

        if ($filter === 'mine') {
            $query->where('user_id', auth()->id());
        }

        $leaves = $query->paginate(10)->withQueryString();

        return view('leave.index', compact('leaves', 'filter'));
    }

    public function create()
    {
        return view('leave.create');
    }

    public function edit(LeaveRequest $leave)
    {
        if ($leave->user_id !== auth()->id()) {
            abort(403);
        }

        return view('leave.edit', compact('leave'));
    }

    public function show(LeaveRequest $leave)
    {
        $user = auth()->user();
        if ($leave->user_id !== $user->id && ! $user->hasAnyRole(['admin', 'hr', 'manager', 'supervisor'])) {
            abort(403);
        }

        return redirect()->route('approvals.show', $leave->id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'nip' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:255',
            'mandatory_document' => 'nullable|string|max:255',
            'period' => 'nullable|in:full,half',
            'cover_by' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $days = \Carbon\Carbon::parse($validated['start_date'])->diffInDays(\Carbon\Carbon::parse($validated['end_date'])) + 1;

        $data = [
            'user_id' => auth()->id(),
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $days,
            'reason' => $validated['reason'] ?? null,
            // default nip/department from authenticated user if not provided
            'nip' => $validated['nip'] ?? auth()->user()?->nip ?? auth()->user()?->employee_id ?? null,
            'department' => $validated['department'] ?? auth()->user()?->department ?? null,
            // if mandatory_document not provided, try to compute a fallback from leave_type
            'mandatory_document' => $validated['mandatory_document'] ?? null,
            'period' => $validated['period'] ?? null,
            'cover_by' => $validated['cover_by'] ?? null,
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('leave_attachments', 'public');
            $data['attachment_path'] = $path;
        }

        $leave = LeaveRequest::create($data);

        // compute mandatory_document server-side if still empty (fallback)
        if (empty($leave->mandatory_document)) {
            $mapping = [
                'sick leave' => ['Sick Letter from Doctor'],
                'annual leave' => ['—'],
                'maternity leave' => ['Maternity Letter from Doctor'],
                'miscarriage leave' => ['Miscarriage Letter form Doctor'],
                'employees get married' => ['Invitation and Married Letter'],
                'marrying children' => ['Invitation and Married Letter'],
                'circumcising children' => ['Circumcising Letter form Doctor'],
                'baptizing a child' => ['Baptizing Letter from Chruch'],
                'wife is giving birth' => ['Maternity Letter from Doctor'],
                'husband/wife parent in-law or child died' => ['Death Letter from Relevant Agencies'],
                'biological grandfather/grandmother brother/sister died' => ['Death Letter from Relevant Agencies'],
                'family died in same house' => ['Death Letter from Relevant Agencies'],
                'menstruation' => ['Sick Letter from Doctor'],
                'hajj during the required time' => ['Letter Hajj from the relevant agency'],
                'umroh during the required time' => ['Letter umroh from the relevant agency'],
                'replace day' => ['—'],
                'other' => ['—'],
            ];

            $key = strtolower(trim($leave->leave_type));
            $docs = $mapping[$key] ?? ['—'];
            $leave->mandatory_document = is_array($docs) ? implode('; ', $docs) : $docs;
            $leave->save();
        }

        // Notifications for new leave submissions are handled by the LeaveRequest model
        // to ensure all creation pathways (controller, Filament, scripts) behave the same.

        // After submitting a leave request, redirect user to the Approvals menu
        // so they can view/manage approval status and related items.
        return redirect()->route('approvals.index')->with('success', 'Leave request submitted.');
    }

    public function update(Request $request, LeaveRequest $leave)
    {
        $actor = auth()->user();
        if (! $actor) {
            abort(403);
        }

        $isAdmin = $actor->hasAnyRole(['admin']);

        $currentCycleLockedToAdmins = false;
        try {
            $owner = $leave->user; // eager-load owner to evaluate contract cycle
            if ($owner && $owner->contract_start_date && method_exists($owner, 'leaveCycleRange')) {
                [$cycleStart, $cycleEnd] = $owner->leaveCycleRange();
                if ($cycleStart && $cycleEnd && $leave->start_date) {
                    $start = \Carbon\Carbon::parse($leave->start_date);
                    $currentCycleLockedToAdmins = $start->between($cycleStart, $cycleEnd, true);
                }
            }
        } catch (\Throwable $e) {
            $currentCycleLockedToAdmins = false;
        }

        if ($currentCycleLockedToAdmins && ! $isAdmin) {
            abort(403, 'Only admin can update leave within the active contract cycle.');
        }

        if ($leave->user_id !== $actor->id && ! $isAdmin) {
            abort(403);
        }

        $validated = $request->validate([
            'leave_type' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'period' => 'nullable|in:full,half',
            'cover_by' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $days = \Carbon\Carbon::parse($validated['start_date'])->diffInDays(\Carbon\Carbon::parse($validated['end_date'])) + 1;

        $data = [
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $days,
            'reason' => $validated['reason'] ?? null,
            'period' => $validated['period'] ?? null,
            'cover_by' => $validated['cover_by'] ?? null,
        ];

        if ($request->hasFile('attachment')) {
            // remove old attachment if exists
            if ($leave->attachment_path) {
                Storage::disk('public')->delete($leave->attachment_path);
            }
            $path = $request->file('attachment')->store('leave_attachments', 'public');
            $data['attachment_path'] = $path;
        }

        $leave->update($data);

        // After update, redirect back to the leave list (do not open the detail view here)
        return redirect()->route('leave.index')->with('success', 'Leave updated.');
    }

    /**
     * Remove the specified leave request.
     */
    public function destroy(LeaveRequest $leave)
    {
        // allow owner, admin or hr to delete
        $user = auth()->user();
        if ($leave->user_id !== $user->id && ! $user->hasAnyRole(['admin', 'hr'])) {
            abort(403);
        }

        // delete attachment if exists
        if ($leave->attachment_path) {
            try {
                Storage::disk('public')->delete($leave->attachment_path);
            } catch (\Exception $e) {
                logger()->warning('Failed to delete attachment: ' . $e->getMessage());
            }
        }

        $leave->delete();

        return redirect()->route('leave.index')->with('success', 'Leave request deleted.');
    }

    public function download(LeaveRequest $leave)
    {
        if ($leave->user_id !== auth()->id() && ! auth()->user()->hasAnyRole(['admin', 'hr'])) {
            abort(403);
        }

        // Use DOMPDF if available
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('leave.pdf', compact('leave'));
            // If preview=1 is present, stream inline so the browser can render it in an iframe
            if (request()->query('preview')) {
                return $pdf->stream('leave-' . $leave->id . '.pdf');
            }

            return $pdf->download('leave-' . $leave->id . '.pdf');
        }

        // Fallback: return HTML as download with .html extension
        $html = view('leave.pdf', compact('leave'))->render();
        $disposition = request()->query('preview') ? 'inline' : 'attachment';
        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => $disposition . '; filename="leave-' . $leave->id . '.html"',
        ]);
    }

    /**
     * Stream the stored attachment (if present) inline so the browser shows a preview
     * instead of downloading the file.
     */
    public function previewAttachment(LeaveRequest $leave)
    {
        if ($leave->user_id !== auth()->id() && ! auth()->user()->hasAnyRole(['admin', 'hr'])) {
            abort(403);
        }

        if (empty($leave->attachment_path)) {
            abort(404);
        }

        $path = storage_path('app/public/' . $leave->attachment_path);
        if (! file_exists($path)) {
            abort(404);
        }

        $mime = @mime_content_type($path) ?: 'application/octet-stream';

        // Use response()->file() with inline disposition to allow the browser to show the file
        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * Render an internal preview page that wraps the PDF/attachment inside the app layout.
     * The view will embed an iframe pointing to the streaming endpoint (download?preview=1)
     * or the attachment preview endpoint so the browser displays the file inside the app.
     */
    public function previewPage(LeaveRequest $leave)
    {
        if ($leave->user_id !== auth()->id() && ! auth()->user()->hasAnyRole(['admin', 'hr'])) {
            abort(403);
        }

        // Prefer generated PDF view (download route with ?preview=1). If an attachment exists and is a PDF,
        // we can also point to the attachment preview route. For now, use generated PDF by default and
        // fallback to attachment preview when requested by query (?attachment=1) or when no PDF is desired.
        if (request()->query('attachment') && $leave->attachment_path) {
            $previewUrl = route('leave.attachment.preview', $leave->id);
        } else {
            $previewUrl = route('leave.download', $leave->id) . '?preview=1';
        }

        return view('leave.preview', compact('leave', 'previewUrl'));
    }
}
