@php
    // Expected variables: $status (string), optional $size (e.g. 'text-sm')
    $st = strtolower(trim($status ?? 'pending'));
    $base = 'inline-flex items-center px-3 py-1 rounded-full font-medium';
    $sizeClass = $size ?? 'text-sm';
    $badgeClass = 'bg-gray-100 text-gray-700';

    if (in_array($st, ['approved', 'accept', 'accepted'])) {
        $badgeClass = 'bg-emerald-100 text-emerald-700';
    } elseif (in_array($st, ['rejected', 'denied', 'deny'])) {
        $badgeClass = 'bg-rose-100 text-rose-700';
    } elseif ($st === 'pending') {
        $badgeClass = 'bg-yellow-100 text-yellow-800';
    }
@endphp

<span class="{{ $base }} {{ $sizeClass }} {{ $badgeClass }}">{{ ucfirst($st) }}</span>
