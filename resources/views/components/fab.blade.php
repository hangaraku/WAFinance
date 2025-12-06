@props(['href', 'color' => 'orange'])

@php
    $colorClasses = [
        'orange' => 'bg-orange-500 hover:bg-orange-600',
        'red' => 'bg-red-600 hover:bg-red-700',
        'green' => 'bg-green-500 hover:bg-green-600',
        'blue' => 'bg-blue-500 hover:bg-blue-600'
    ];
@endphp

<div class="fixed bottom-24 right-4">
    <a href="{{ $href }}" class="w-14 h-14 {{ $colorClasses[$color] }} text-white rounded-full shadow-lg transition-colors flex items-center justify-center">
        {{ $slot }}
    </a>
</div>
