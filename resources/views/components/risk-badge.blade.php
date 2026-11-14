@props(['level' => null, 'score' => null])

@php
    $levelNormalized = strtolower(trim((string)$level));

    if (empty($levelNormalized) && is_numeric($score)) {
        $scoreVal = (int) $score;
        if ($scoreVal <= 28) {
            $levelNormalized = 'low';
        } elseif ($scoreVal <= 56) {
            $levelNormalized = 'medium';
        } else {
            $levelNormalized = 'high';
        }
    }

    $classes = match($levelNormalized) {
        'low' => 'bg-green-100 text-green-800 border-green-200',
        'medium' => 'bg-amber-100 text-amber-800 border-amber-200',
        'high' => 'bg-red-100 text-red-800 border-red-200 font-semibold',
        default => 'bg-gray-100 text-gray-700 border-gray-200',
    };

    $label = match($levelNormalized) {
        'low' => 'Low Risk',
        'medium' => 'Medium Risk',
        'high' => 'High Risk',
        default => 'Unassessed',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {$classes}"]) }}>
    {{ $label }}
</span>
