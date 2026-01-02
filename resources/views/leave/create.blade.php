@extends('layouts.app')

@section('title', 'Apply - Jeda')

@section('content')
    <main class="max-w-3xl mx-auto p-6 use-roboto">

        <!-- Success toast -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="fixed right-6 top-6 z-50">
                <div class="flex items-start gap-3 bg-emerald-600 text-white px-4 py-3 rounded-lg shadow-lg">
                    <svg class="w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <div class="text-sm">{{ session('success') }}</div>
                    <button @click="show=false" class="ms-3 opacity-90 hover:opacity-100">✕</button>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold">Leave Application</h1>
                    <p class="text-sm text-gray-500 mt-1">Please fill the form below to submit your leave request</p>
                </div>
            </div>

            <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-semibold mb-2">Full name</label>
                    <input name="name" type="text"
                        class="w-full rounded-xl bg-slate-50 border border-gray-100 p-3 text-sm"
                        placeholder="Your full name" value="{{ old('name', auth()->user()?->name ?? '') }}" readonly
                        aria-readonly="true" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">NIP:</label>
                        <input name="nip" type="text" class="w-full rounded-md bg-gray-100 p-3 text-sm" readonly
                            aria-readonly="true" placeholder="NIP (optional)"
                            value="{{ auth()->user()?->nip ?? (auth()->user()?->employee_id ?? '') }}" />
                    </div>
                    <!-- Using only NIP as the single identity field -->
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Department</label>
                    @php $deptValue = old('department') ?? (auth()->user()?->department ?? ''); @endphp
                    <input type="hidden" name="department" value="{{ $deptValue }}" />
                    <div class="w-full rounded-xl bg-slate-50 border border-gray-100 p-3 text-sm text-slate-700">
                        {{ $deptValue ?: '—' }}</div>
                </div>


                <div>
                    <label class="block text-sm font-semibold mb-2">Leave Type <span class="text-red-500">*</span></label>
                    <select name="leave_type" class="w-full rounded-xl border border-gray-100 bg-white p-3 text-sm"
                        required>
                        <option value="">Choose leave type</option>
                        <option value="sick leave">Sick Leave</option>
                        <option value="annual leave">Annual Leave</option>
                        <option value="maternity leave">Maternity Leave</option>
                        <option value="miscarriage leave">Miscarriage Leave</option>
                        <option value="employees get married">Employees Get Married</option>
                        <option value="marrying children">Marrying Children</option>
                        <option value="circumcising children">Circumcising Children</option>
                        <option value="baptizing a child">Baptizing A Child</option>
                        <option value="wife is giving birth">Wife Is Giving Birth / Miscarried</option>
                        <option value="husband/wife parent in-law or child died">Husband/Wife Parent In-Law Or Child Died
                        </option>
                        <option value="biological grandfather/grandmother brother/sister died">Biological
                            Grandfather/Grandmother Brother/Sister Died</option>
                        <option value="family died in same house">Family Died In Same House</option>
                        <option value="menstruation">Menstruation</option>
                        <option value="hajj during the required time">Hajj During the Required Time</option>
                        <option value="umroh during the required time">Umroh During the Required Time</option>
                        <option value="replace day">Replace Day</option>
                        <option value="other">Other</option>
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
                    <input id="mandatory-document-hidden" type="hidden" name="mandatory_document" value="" />
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Select Dates</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Start Date</label>
                            <input id="start-date" name="start_date" type="date" required
                                class="w-full rounded-xl border border-gray-100 p-3 text-sm"
                                value="{{ old('start_date', '') }}" />
                            @error('start_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">End Date</label>
                            <input id="end-date" name="end_date" type="date" required
                                class="w-full rounded-xl border border-gray-100 p-3 text-sm"
                                value="{{ old('end_date', '') }}" />
                            @error('end_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Select Period</label>
                    <select name="period" class="w-full rounded-xl border border-gray-100 bg-white p-3 text-sm">
                        <option>Period of leave</option>
                        <option value="full">Full day</option>
                        <option value="half">Half day</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Reason</label>
                    <textarea name="reason" rows="6" class="w-full rounded-xl border border-gray-100 p-4 text-sm"
                        placeholder="Enter reason">{{ old('reason') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Attachment (optional)</label>
                    <input type="file" name="attachment"
                        class="w-full rounded-xl border border-gray-100 p-2 bg-white text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Jobs Will Be Covered By</label>
                    <input name="cover_by" type="text" class="w-full rounded-xl border border-gray-100 p-3 text-sm"
                        placeholder="Who will cover your work" value="{{ old('cover_by') }}" />
                </div>

                <div class="pt-4">
                    <button id="apply-btn" type="submit"
                        class="w-full rounded-full text-slate-900 py-3 font-semibold shadow-lg hover:opacity-95"
                        style="background: linear-gradient(90deg, #83A4D4 0%, #B6FBFF 100%);">
                        Submit Leave
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

            // restore old selected leave_type from server (if any)
            try {
                const initialType = @json(old('leave_type', ''));
                if (typeEl && initialType) {
                    typeEl.value = initialType;
                }
            } catch (err) {
                // ignore JSON parse errors
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

                // set minimum allowed end date
                endEl.setAttribute('min', startVal);

                // if current end date is before start, clamp it
                if (endEl.value && endEl.value < startVal) {
                    endEl.value = startVal;
                }
            }

            if (startEl) {
                startEl.addEventListener('change', clampEndToStart);
                // run once on load to initialize state (handles old() values)
                clampEndToStart();
            }
        })();
        // Mobile tap fallback: if touchend occurs on the button container but click doesn't fire,
        // forward the touch as a click. This helps on some Android webviews where overlays
        // or -webkit-tap-highlight interactions can swallow the click event.
        (function() {
            const btn = document.getElementById('apply-btn');
            if (!btn) return;
            let touched = false;
            btn.addEventListener('touchstart', () => touched = true, {
                passive: true
            });
            btn.addEventListener('touchend', (e) => {
                if (touched) {
                    // If the native click was not produced, trigger submit via form submission
                    try {
                        const form = btn.closest('form');
                        if (form) {
                            form.requestSubmit ? form.requestSubmit() : form.submit();
                        }
                    } catch (err) {
                        /* ignore */
                    }
                    touched = false;
                }
            }, {
                passive: true
            });
        })();
    </script>
@endsection
