@php
    // Inputs: $start, $end (strings or Carbon); optional $legacy
    $s = $start ?? null;
    $e = $end ?? null;
    // fallback to legacy if provided and start/end empty
    if (empty($s) && isset($legacy)) {
        $s = $legacy;
    }
    try {
        $sFormatted = $s ? \Illuminate\Support\Carbon::parse($s)->format('d M Y') : null;
    } catch (\Throwable $ex) {
        $sFormatted = $s ?: null;
    }
    try {
        $eFormatted = $e ? \Illuminate\Support\Carbon::parse($e)->format('d M Y') : null;
    } catch (\Throwable $ex) {
        $eFormatted = $e ?: null;
    }

    // Decide output: if neither exists, render empty string (do not print '-').
    if (!$sFormatted && !$eFormatted) {
        $output = '';
    } elseif ($sFormatted && !$eFormatted) {
        $output = $sFormatted;
    } elseif (!$sFormatted && $eFormatted) {
        $output = $eFormatted;
    } else {
        $output = $sFormatted . ' to ' . $eFormatted;
    }
@endphp

{!! $output !!}
