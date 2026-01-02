<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application for Leave of Absence - {{ $leave->user?->name ?? ($leave->name ?? '') }}</title>
    <style>
        @page {
            margin: 18pt;
        }

        body {
            font-family: 'Helvetica Neue', Arial, 'Times New Roman', serif;
            color: #111;
            font-size: 12px;
            margin: 0;
        }

        .page {
            padding: 18px;
        }

        /* Header: left logo, centered org block, right-side meta */
        .header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .header-left {
            width: 140px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .header-left .logo {
            max-width: 120px;
            height: 50px;
            object-fit: contain;
            display: block;
            margin-top: 4px;
            /* nudge down for alignment */
        }

        .org {
            flex: 1 1 auto;
            text-align: center;
            padding-top: 2px;
        }

        .org h1 {
            margin: 0;
            font-size: 14px;
            letter-spacing: 1px;
            font-weight: 700;
            line-height: 1.05;
        }

        .org .sub {
            font-size: 11px;
            margin-top: 4px;
            color: #333;
            line-height: 1.1;
        }

        .header-right {
            width: 140px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
            font-size: 11px;
            gap: 4px;
        }

        /* small visual tweak for header right area */
        .header-right {
            margin-top: 2px;
        }

        .doc-title {
            margin-top: 12px;
            border-top: 2px solid #111;
            border-bottom: 1px solid #ddd;
            padding: 8px 0;
            text-align: center;
            font-weight: 700;
            background: #fafafa
        }

        /* More generous spacing so printed rows are not too cramped */
        .meta-table {
            width: 100%;
            margin-top: 16px;
            border-collapse: separate;
            border-spacing: 8px 8px;
            box-sizing: border-box
        }

        .meta-table td,
        .meta-table th {
            padding: 14px 16px;
            vertical-align: middle;
            min-height: 56px;
            box-sizing: border-box
        }

        /* make label/value boxes visually uniform */
        .meta-table .label {
            width: 25%;
            font-weight: 700;
            border: 1px solid #ccc;
            background: #f7f7f8;
            box-sizing: border-box
        }

        .meta-table .value {
            width: 25%;
            border: 1px solid #ccc;
            box-sizing: border-box
        }

        .leave-table {
            width: 100%;
            margin-top: 22px;
            border-collapse: separate;
            border-spacing: 8px 8px;
            box-sizing: border-box
        }

        /* Leave table: treat as label/value rows (no heavy borders) */
        .leave-table th,
        .leave-table td {
            border: none;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
            box-sizing: border-box;
        }

        .leave-table tr {
            border-bottom: 1px solid #f3f3f3;
        }

        .leave-table td.label {
            width: 35%;
            font-weight: 700;
            padding-right: 12px;
        }

        .leave-table td.value {
            width: 65%;
        }

        /* Ensure long inline values (like Mandatory Document) wrap cleanly */
        .leave-table td.value span {
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
            display: inline-block;
            max-width: 100%;
        }

        /* make the End Date column larger and prevent wrapping */
        .end-date-cell {
            text-align: center;
            white-space: nowrap;
            min-width: 120px;
            width: 120px;
            vertical-align: middle
        }

        .status-row {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .status-row td {
            border: 1px solid #ccc;
            padding: 14px 16px;
            min-height: 64px;
            vertical-align: middle
        }

        .sign-table {
            width: 100%;
            margin-top: 26px;
            border-collapse: collapse;
        }

        .sign-table td {
            padding: 12px 10px;
            text-align: center;
            vertical-align: bottom;
        }

        .sign-role {
            font-size: 12px;
            color: #333;
            margin-bottom: 6px
        }

        /* Larger signature space and consistent width for signature boxes */
        .sig-space {
            height: 140px;
            border-bottom: 1px solid #000;
            width: 80%;
            margin: 0 auto 8px auto;
            display: block
        }

        .sign-name {
            margin-top: 8px;
            font-weight: 600
        }

        .small {
            font-size: 11px;
            color: #333;
        }

        @media print {
            a[href]:after {
                content: " (" attr(href) ")";
                color: #666;
                font-size: 10px;
            }
        }

        /* Mobile / small-screen adjustments for PDF viewing on phones */
        @media (max-width: 639px) {
            body {
                font-size: 11px;
            }

            .page {
                padding: 10px;
            }

            /* Stack header pieces for small screens */
            .header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 6px;
            }

            .header-left {
                width: auto;
            }

            .header-left .logo {
                margin-top: 0;
            }

            .org {
                padding-top: 0;
            }

            .header-right {
                width: auto;
                align-items: center;
                font-size: 12px;
            }

            /* Meta table: make label/value boxes stack in two columns and then full width if needed */
            .meta-table {
                border-spacing: 6px 6px;
            }

            .meta-table tr {
                display: flex;
                flex-wrap: wrap;
            }

            .meta-table td.label,
            .meta-table td.value {
                display: block;
                width: 48%;
                padding: 10px 8px;
                box-sizing: border-box;
            }

            /* Make label clearer and smaller on mobile */
            .meta-table .label {
                font-weight: 700;
                font-size: 11px;
            }

            /* Leave table: keep as a compact table on small screens */
            .leave-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            .leave-table thead {
                display: table-header-group;
            }

            .leave-table tbody {
                display: table-row-group;
            }

            .leave-table tr {
                display: table-row;
            }

            .leave-table th,
            .leave-table td {
                display: table-cell;
                padding: 8px 10px;
                border: 1px solid #e6e6e6;
                vertical-align: middle;
            }

            .leave-table th {
                background: #f7f7f8;
                font-weight: 700;
            }

            /* Compact layout: center small columns like dates */
            .leave-table td {
                font-size: 13px;
            }

            /* removed duplicated ::before labels to avoid duplicate text in label/value table */

            /* Status row: display side-by-side (horizontal) on small screens, wrap if needed */
            .status-row tr {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                align-items: stretch;
            }

            .status-row td {
                display: flex;
                flex: 1 1 30%;
                min-width: 120px;
                padding: 8px;
                box-sizing: border-box;
                align-items: center;
                justify-content: center;
            }

            .sig-space {
                height: 80px;
                width: 90%;
            }

            /* Make signatures line up horizontally on small screens */
            .sign-table tr {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }

            .sign-table td {
                display: flex;
                flex-direction: column;
                flex: 1 1 23%;
                min-width: 140px;
                padding: 8px 6px;
                box-sizing: border-box;
                align-items: center;
            }

            .org h1 {
                font-size: 14px;
            }

            .org .sub {
                font-size: 11px;
            }

            .doc-title {
                font-size: 13px;
                padding: 6px 0;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="header">
            <div class="header-left">
                <!-- prefer project logos in public/images -->
                @php
                    $logoPath = file_exists(public_path('images/logo_ivssi.png'))
                        ? asset('images/logo_ivssi.png')
                        : (file_exists(public_path('images/logo.png'))
                            ? asset('images/logo.png')
                            : null);
                @endphp
                @if ($logoPath)
                    <img src="{{ $logoPath }}" class="logo" alt="logo">
                @else
                    <div style="font-weight:700; font-size:14px;">INDORAMA</div>
                @endif
            </div>

            <div class="org">
                <h1>INDORAMA VENTURES SUSTAINABLE SOLUTIONS INDONESIA</h1>
            </div>

            <div class="header-right">
                <div>Document Title</div>
                <div>Revision</div>
            </div>
        </div>

        <div class="doc-title">Application for Leave of Absence</div>

        <table class="meta-table">
            <tr>
                <td class="label">Name</td>
                <td class="value">{{ $leave->user?->name ?? ($leave->name ?? '—') }}</td>
                <td class="label">Employee No</td>
                <td class="value">{{ $leave->user?->employee_id ?? ($leave->nip ?? ($leave->user?->nip ?? '—')) }}</td>
            </tr>
            <tr>
                <td class="label">Department</td>
                <td class="value">{{ $leave->department ?? ($leave->user?->department ?? '—') }}</td>
                <td class="label">Date Filed</td>
                <td class="value">@php
                    try {
                        echo optional($leave->created_at)->format('d-m-Y') ?? now()->format('d-m-Y');
                    } catch (\Throwable $e) {
                        echo $leave->created_at ?? now()->format('d-m-Y');
                    }
                @endphp</td>
            </tr>
        </table>

        <table class="leave-table">
            <tbody>
                <tr>
                    <td class="label" style="width:35%; font-weight:700;">Leave:</td>
                    <td class="value">{{ $leave->type ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label" style="font-weight:700;">Mandatory Document:</td>
                    <td class="value">
                        @php
                            // mandatory_document may be stored as semicolon-separated list; render inline.
                            $md = $leave->mandatory_document ?? ($leave->mandatory_documents ?? null);
                            $items = [];
                            if (is_string($md) && strpos($md, ';') !== false) {
                                $items = array_filter(array_map('trim', explode(';', $md)));
                            } elseif (is_string($md) && trim($md) !== '') {
                                $items = [trim($md)];
                            }

                            // filter out placeholder em-dash values
                            $items = array_values(
                                array_filter($items, function ($v) {
                                    return !in_array(trim($v), ['—', '-', '']);
                                }),
                            );

                            // fallback mapping based on leave type if no explicit items found
                            if (count($items) === 0) {
                                $typeKey = strtolower(trim($leave->type ?? ''));
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
                                    'husband/wife parent in-law or child died' => [
                                        'Death Letter from Relevant Agencies',
                                    ],
                                    'biological grandfather/grandmother brother/sister died' => [
                                        'Death Letter from Relevant Agencies',
                                    ],
                                    'family died in same house' => ['Death Letter from Relevant Agencies'],
                                    'menstruation' => ['Sick Letter from Doctor'],
                                    'hajj during the required time' => ['Letter Hajj from the relevant agency'],
                                    'umroh during the required time' => ['Letter umroh from the relevant agency'],
                                    'replace day' => ['—'],
                                    'other' => ['—'],
                                ];
                                $items = $mapping[$typeKey] ?? [];
                                $items = array_values(
                                    array_filter($items, fn($v) => !in_array(trim($v), ['—', '-', ''])),
                                );
                            }
                        @endphp
                        @if (count($items) > 0)
                            @php $inline = implode(', ', $items); @endphp
                            <span>{{ $inline }}</span>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label" style="font-weight:700;">Days/Months:</td>
                    <td class="value">
                        @php
                            $daysDisplay = '—';
                            if (!empty($leave->start_date) && !empty($leave->end_date)) {
                                try {
                                    $s = \Illuminate\Support\Carbon::parse($leave->start_date);
                                    $e = \Illuminate\Support\Carbon::parse($leave->end_date);
                                    $daysDisplay = $s->diffInDays($e) + 1 . ' days';
                                } catch (\Throwable $ex) {
                                    $daysDisplay = '—';
                                }
                            }
                        @endphp
                        {{ $daysDisplay }}
                    </td>
                </tr>
                <tr>
                    <td class="label" style="font-weight:700;">Start Date:</td>
                    <td class="value">
                        @if (!empty($leave->start_date))
                            {{ \Illuminate\Support\Carbon::parse($leave->start_date)->format('d-m-Y') }}@else—
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label" style="font-weight:700;">End Date:</td>
                    <td class="value">
                        @if (!empty($leave->end_date))
                            {{ \Illuminate\Support\Carbon::parse($leave->end_date)->format('d-m-Y') }}@else—
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="status-row">
            <tr>
                <td style="width:40%">Leave Status:
                    <strong>{{ ucfirst($leave->final_status ?? ($leave->status ?? 'pending')) }}</strong></td>
                <td style="width:30%">Balance Before Leave: @php
                    $bb = isset($balance_before) ? $balance_before : $leave->balance_before ?? null;
                    if (is_numeric($bb)) {
                        echo $bb . ' days';
                    } elseif (is_string($bb) && trim($bb) !== '') {
                        echo $bb;
                    } else {
                        echo '—';
                    }
                @endphp</td>
                <td style="width:30%">Balance After Leave: @php
                    $ba = isset($balance_after) ? $balance_after : $leave->balance_after ?? null;
                    if (is_numeric($ba)) {
                        echo $ba . ' days';
                    } elseif (is_string($ba) && trim($ba) !== '') {
                        echo $ba;
                    } else {
                        echo '—';
                    }
                @endphp</td>
            </tr>
        </table>

        <div style="margin-top:8px;">
            <div class="small">I fully understand that I am expected to report for work after the end date/time I
                indicated herein. I certify that the reason for leave indicated herein is true and correct to the best
                of my knowledge.</div>
        </div>

        <table class="sign-table">
            <tr>
                <td style="width:25%">
                    <div class="sig-space"></div>
                    <div class="sign-role">Employee Signature</div>
                    <div class="sign-name">{{ $leave->user?->name ?? '—' }}</div>
                </td>
                <td style="width:25%">
                    <div class="sig-space"></div>
                    <div class="sign-role">Approved by</div>
                    <div class="sign-name">Immediate Superior</div>
                </td>
                <td style="width:25%">
                    <div class="sig-space"></div>
                    <div class="sign-role">Head of Department</div>
                    <div class="sign-name">{{ $leave->hodApprover?->name ?? ($leave->hod_approver ?? '—') }}</div>
                </td>
                <td style="width:25%">
                    <div class="sig-space"></div>
                    <div class="sign-role">HR Officer</div>
                    <div class="sign-name">
                        {{ $leave->hrApprover?->name ?? ($hrApproverName ?? ($leave->hr_approver ?? '—')) }}</div>
                </td>
            </tr>
        </table>

        <div style="margin-top:6px; font-size:11px; color:#666">*** end of form ***</div>
    </div>
</body>

</html>
