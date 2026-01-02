@extends('layouts.app')

@section('title', 'Edit Leave')

@section('content')
    <main class="max-w-3xl mx-auto p-6 use-roboto">

        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold">Edit Leave Application</h1>
                    <p class="text-sm text-gray-500 mt-1">Update your leave request below and save changes.</p>
                </div>
            </div>

            <form action="{{ route('leave.update', $leave->id) }}" method="POST" enctype="multipart/form-data"
                class="space-y-6">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-semibold mb-2">Full name</label>
                    <input name="name" type="text"
                        class="w-full rounded-xl bg-slate-50 border border-gray-100 p-3 text-sm"
                        placeholder="Your full name"
                        value="{{ old('name', $leave->user?->name ?? (auth()->user()?->name ?? '')) }}" readonly
                        aria-readonly="true" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">NIP:</label>
                        <input name="nip" type="text" class="w-full rounded-md bg-gray-100 p-3 text-sm" readonly
                            aria-readonly="true" placeholder="NIP (optional)"
                            value="{{ old('nip', $leave->nip ?? (auth()->user()?->nip ?? (auth()->user()?->employee_id ?? ''))) }}" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Department</label>
                    @php $deptValue = old('department', $leave->department ?? auth()->user()?->department ?? ''); @endphp
                    <input type="hidden" name="department" value="{{ $deptValue }}" />
                    <div class="w-full rounded-xl bg-slate-50 border border-gray-100 p-3 text-sm text-slate-700">
                        {{ $deptValue ?: '—' }}</div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Leave Type <span class="text-red-500">*</span></label>
                    <select name="leave_type" class="w-full rounded-xl border border-gray-100 bg-white p-3 text-sm"
                        required>
                        <option value="">Choose leave type</option>
                        <option value="sick leave"
                            {{ old('leave_type', $leave->leave_type) === 'sick leave' ? 'selected' : '' }}>Sick Leave
                        </option>
                        <option value="annual leave"
                            {{ old('leave_type', $leave->leave_type) === 'annual leave' ? 'selected' : '' }}>Annual Leave
                        </option>
                        <option value="maternity leave"
                            {{ old('leave_type', $leave->leave_type) === 'maternity leave' ? 'selected' : '' }}>Maternity
                            Leave</option>
                        <option value="miscarriage leave"
                            {{ old('leave_type', $leave->leave_type) === 'miscarriage leave' ? 'selected' : '' }}>
                            Miscarriage Leave</option>
                        <option value="employees get married"
                            {{ old('leave_type', $leave->leave_type) === 'employees get married' ? 'selected' : '' }}>
                            Employees Get Married</option>
                        <option value="marrying children"
                            {{ old('leave_type', $leave->leave_type) === 'marrying children' ? 'selected' : '' }}>Marrying
                            Children</option>
                        <option value="circumcising children"
                            {{ old('leave_type', $leave->leave_type) === 'circumcising children' ? 'selected' : '' }}>
                            Circumcising Children</option>
                        <option value="baptizing a child"
                            {{ old('leave_type', $leave->leave_type) === 'baptizing a child' ? 'selected' : '' }}>Baptizing
                            A Child</option>
                        <option value="wife is giving birth"
                            {{ old('leave_type', $leave->leave_type) === 'wife is giving birth' ? 'selected' : '' }}>Wife
                            Is Giving Birth / Miscarried</option>
                        <option value="husband/wife parent in-law or child died"
                            {{ old('leave_type', $leave->leave_type) === 'husband/wife parent in-law or child died' ? 'selected' : '' }}>
                            Husband/Wife Parent In-Law Or Child Died</option>
                        <option value="biological grandfather/grandmother brother/sister died"
                            {{ old('leave_type', $leave->leave_type) === 'biological grandfather/grandmother brother/sister died' ? 'selected' : '' }}>
                            Biological Grandparent/Relative Died</option>
                        <option value="family died in same house"
                            {{ old('leave_type', $leave->leave_type) === 'family died in same house' ? 'selected' : '' }}>
                            Family Died In Same House</option>
                        <option value="menstruation"
                            {{ old('leave_type', $leave->leave_type) === 'menstruation' ? 'selected' : '' }}>Menstruation
                        </option>
                        <option value="hajj during the required time"
                            {{ old('leave_type', $leave->leave_type) === 'hajj during the required time' ? 'selected' : '' }}>
                            Hajj During the Required Time</option>
                        <option value="umroh during the required time"
                            {{ old('leave_type', $leave->leave_type) === 'umroh during the required time' ? 'selected' : '' }}>
                            Umroh During the Required Time</option>
                        <option value="replace day"
                            {{ old('leave_type', $leave->leave_type) === 'replace day' ? 'selected' : '' }}>Replace Day
                        </option>
                        <option value="other" {{ old('leave_type', $leave->leave_type) === 'other' ? 'selected' : '' }}>
                            Other</option>
                    </select>
                    @error('leave_type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Mandatory Document</label>
                    <div id="mandatory-document-list"
                        class="w-full rounded-xl border border-gray-100 p-3 text-sm min-h-[48px] text-slate-700 bg-slate-50">
                        <em class="text-slate-500">Mandatory document will appear here</em>
                    </div>
                    <input id="mandatory-document-hidden" type="hidden" name="mandatory_document"
                        value="{{ old('mandatory_document', $leave->mandatory_document ?? '') }}" />
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Select Dates</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Start Date</label>
                            <input id="start-date" name="start_date" type="date" required
                                class="w-full rounded-xl border border-gray-100 p-3 text-sm"
                                value="{{ old('start_date', $leave->start_date) }}" />
                            @error('start_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">End Date</label>
                            <input id="end-date" name="end_date" type="date" required
                                class="w-full rounded-xl border border-gray-100 p-3 text-sm"
                                value="{{ old('end_date', $leave->end_date) }}" />
                            @error('end_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Select Period</label>
                    <select name="period" class="w-full rounded-xl border border-gray-100 bg-white p-3 text-sm">
                        <option value="">Period of leave</option>
                        <option value="full" {{ old('period', $leave->period) === 'full' ? 'selected' : '' }}>Full day
                        </option>
                        <option value="half" {{ old('period', $leave->period) === 'half' ? 'selected' : '' }}>Half day
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Reason</label>
                    <textarea name="reason" rows="6" class="w-full rounded-xl border border-gray-100 p-4 text-sm"
                        placeholder="Enter reason">{{ old('reason', $leave->reason) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Attachment (optional)</label>
                    <input type="file" name="attachment"
                        class="w-full rounded-xl border border-gray-100 p-2 bg-white text-sm" />
                    @if ($leave->attachment_path)
                        <div class="mt-2 text-sm"><a href="{{ route('leave.preview', $leave->id) }}?attachment=1"
                                target="_blank" class="text-indigo-600 underline">Current attachment</a></div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Jobs Will Be Covered By</label>
                    <input name="cover_by" type="text" class="w-full rounded-xl border border-gray-100 p-3 text-sm"
                        placeholder="Who will cover your work" value="{{ old('cover_by', $leave->cover_by) }}" />
                </div>

                <div class="pt-4">
                    <button id="apply-btn" type="submit"
                        class="w-full rounded-full text-slate-900 py-3 font-semibold shadow-lg hover:opacity-95"
                        style="background: linear-gradient(90deg, #83A4D4 0%, #B6FBFF 100%);">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>

    </main>

    <script>
        (function() {
            const mapping = {
                'sick leave': ['Sick Letter from Doctor'],
                'annual leave': ['—'],
                'maternity leave': ['Maternity Letter from Doctor'],
                'miscarriage leave': ['Miscarriage Letter form Doctor'],
                'employees get married': ['Invitation and Married Letter'],
                'marrying children': ['Invitation and Married Letter'],
                'circumcising children': ['Circumcising Letter form Doctor'],
                'baptizing a child': ['Baptizing Letter from Chruch'],
                'wife is giving birth': ['Maternity Letter from Doctor'],
                'husband/wife parent in-law or child died': ['Death Letter from Relevant Agencies'],
                'biological grandfather/grandmother brother/sister died': ['Death Letter from Relevant Agencies'],
                'family died in same house': ['Death Letter from Relevant Agencies'],
                'menstruation': ['Sick Letter from Doctor'],
                'hajj during the required time': ['Letter Hajj from the relevant agency'],
                'umroh during the required time': ['Letter umroh from the relevant agency'],
                'replace day': ['—'],
                'other': ['—']
            };

            const typeEl = document.querySelector('select[name="leave_type"]');
            const listEl = document.getElementById('mandatory-document-list');
            const hiddenEl = document.getElementById('mandatory-document-hidden');

            // restore initial selected leave_type from server (if any)
            try {
                const initialType = @json(old('leave_type', $leave->leave_type ?? ''));
                if (typeEl && initialType) {
                    typeEl.value = initialType;
                }
            } catch (err) {
                // ignore
            }

            function updateMandatory() {
                const v = (typeEl.value || '').toString().toLowerCase().trim();
                const docs = mapping[v] || ['—'];

                if (!listEl) return;
                if (!docs || docs.length === 0) {
                    listEl.innerHTML = '<em class="text-slate-500">—</em>';
                } else if (docs.length === 1) {
                    listEl.innerHTML = '<div>' + docs[0] + '</div>';
                } else {
                    const items = docs.map(d => '<li>' + d + '</li>').join('');
                    listEl.innerHTML = '<ul class="list-disc pl-5">' + items + '</ul>';
                }

                if (hiddenEl) hiddenEl.value = docs.join('; ');
            }

            if (typeEl) {
                typeEl.addEventListener('change', updateMandatory);
                updateMandatory();
            }

            // Ensure end date cannot be before start date
            const startEl = document.getElementById('start-date');
            const endEl = document.getElementById('end-date');

            function clampEndToStart() {
                if (!startEl || !endEl) return;
                const startVal = startEl.value;
                if (!startVal) {
                    endEl.removeAttribute('min');
                    return;
                }

                endEl.setAttribute('min', startVal);

                if (endEl.value && endEl.value < startVal) {
                    endEl.value = startVal;
                }
            }

            if (startEl) {
                startEl.addEventListener('change', clampEndToStart);
                clampEndToStart();
            }
        })();

        // Mobile tap fallback for submit button
        (function() {
            const btn = document.getElementById('apply-btn');
            if (!btn) return;
            let touched = false;
            btn.addEventListener('touchstart', () => touched = true, {
                passive: true
            });
            btn.addEventListener('touchend', (e) => {
                if (touched) {
                    try {
                        const form = btn.closest('form');
                        if (form) {
                            form.requestSubmit ? form.requestSubmit() : form.submit();
                        }
                    } catch (err) {}
                    touched = false;
                }
            }, {
                passive: true
            });
        })();
    </script>
@endsection
