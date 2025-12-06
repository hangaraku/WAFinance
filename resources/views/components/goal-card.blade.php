@props(['goal'])

@php
    $percentage = min(($goal->current_amount / $goal->target_amount) * 100, 100);
    $colorClass = $percentage > 80 ? 'text-green-600' : ($percentage > 60 ? 'text-yellow-600' : 'text-blue-600');
@endphp

<div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-3">
        <h4 class="font-medium text-gray-900">{{ $goal->name }}</h4>
        <span class="text-xs text-gray-500">{{ $goal->target_date }}</span>
    </div>
    
    <p class="text-sm text-gray-600 mb-3">{{ Str::limit($goal->description, 60) }}</p>
    
    <div class="space-y-2">
        <div class="flex justify-between text-sm">
            <span class="text-gray-500">Target</span>
            <span class="font-medium">Rp {{ number_format($goal->target_amount) }}</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-500">Terkumpul</span>
            <span class="font-medium {{ $colorClass }}">Rp {{ number_format($goal->current_amount) }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
        </div>
        <div class="text-right text-xs text-gray-500">{{ round($percentage, 1) }}%</div>
    </div>
</div>
