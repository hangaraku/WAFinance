@props(['href', 'color' => 'orange'])

@php
    $colorClasses = [
        'orange' => 'bg-orange-500 hover:bg-orange-600',
        'red' => 'bg-red-600 hover:bg-red-700',
        'green' => 'bg-green-500 hover:bg-green-600',
        'blue' => 'bg-blue-500 hover:bg-blue-600'
    ];
@endphp

<a href="{{ $href }}" class="flex items-center justify-center space-x-2 {{ $colorClasses[$color] }} text-white py-3 px-4 rounded-lg transition-colors">
    @if(isset($icon))
        <div class="icon">
            {{ $icon }}
        </div>
    @endif
    @if(isset($text))
        <span class="font-medium">{{ $text }}</span>
    @endif
</a>
