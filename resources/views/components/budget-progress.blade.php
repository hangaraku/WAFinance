@props(['budget', 'spent'])

@php
    $percentage = min(($spent / $budget->amount) * 100, 100);
    $colorClass = $percentage > 80 ? 'text-red-600' : ($percentage > 60 ? 'text-yellow-600' : 'text-green-600');
@endphp

<div class="space-y-2">
    <div class="flex justify-between items-center">
        <span class="text-sm font-medium text-gray-700">{{ $budget->category->name }}</span>
        <span class="text-sm text-gray-500">Rp {{ number_format($budget->amount) }}</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2">
        <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
    </div>
    <div class="flex justify-between text-xs text-gray-500">
        <span>Terpakai: Rp {{ number_format($spent) }}</span>
        <span class="{{ $colorClass }}">{{ round($percentage, 1) }}%</span>
    </div>
</div>
