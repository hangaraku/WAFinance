@props(['currentMonth', 'currentYear', 'prevMonth', 'prevYear', 'nextMonth', 'nextYear'])

<div class="flex items-center justify-center mb-6">
    <a href="{{ route('transactions', ['month' => $prevMonth, 'year' => $prevYear]) }}" 
       class="p-2 text-gray-600 hover:text-orange-500 transition-colors {{ $prevMonth == 12 && $prevYear < 2020 ? 'opacity-50 pointer-events-none' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
    </a>
    
    <div class="text-center mx-4">
        <div class="text-lg font-semibold text-gray-900">{{ date('M Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) }}</div>
        <div class="text-xs text-gray-500">{{ $currentMonth == date('n') && $currentYear == date('Y') ? __('common.this_month') : '' }}</div>
    </div>
    
    <a href="{{ route('transactions', ['month' => $nextMonth, 'year' => $nextYear]) }}" 
       class="p-2 text-gray-600 hover:text-orange-500 transition-colors {{ $nextMonth == 1 && $nextYear > 2030 ? 'opacity-50 pointer-events-none' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </a>
</div>
