@props(['status'])

@php
    $statusNormalized = strtolower(trim((string)$status));

    $classes = match($statusNormalized) {
        'paid', 'paid_on_time', 'active' => 'bg-green-100 text-green-800 border-green-200',
        'upcoming' => 'bg-amber-100 text-amber-800 border-amber-200',
        'overdue', 'paid_late' => 'bg-red-100 text-red-800 border-red-200 font-semibold',
        'inactive' => 'bg-gray-100 text-gray-700 border-gray-300',
        default => 'bg-gray-100 text-gray-800 border-gray-200',
    };

    $label = match($statusNormalized) {
        'paid_on_time' => 'Paid On Time',
        'paid_late' => 'Paid Late',
        default => ucwords(str_replace('_', ' ', $statusNormalized)),
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {$classes}"]) }}>
    {{ $label }}
</span>
