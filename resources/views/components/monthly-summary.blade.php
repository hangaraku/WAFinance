@props(['income', 'expenses', 'total'])

<div class="grid grid-cols-3 gap-2 mb-4">
    <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-100">
        <div class="text-center">
            <p class="text-xs text-gray-500 mb-1">{{ __('transactions.income') }}</p>
            <p class="text-sm font-bold text-blue-600">Rp {{ number_format($income) }}</p>
        </div>
    </div>
    
    <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-100">
        <div class="text-center">
            <p class="text-xs text-gray-500 mb-1">{{ __('transactions.expense') }}</p>
            <p class="text-sm font-bold text-red-600">Rp {{ number_format($expenses) }}</p>
        </div>
    </div>
    
    <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-100">
        <div class="text-center">
            <p class="text-xs text-gray-500 mb-1">{{ __('transactions.total') }}</p>
            <p class="text-sm font-bold {{ $total >= 0 ? 'text-green-600' : 'text-red-600' }}">
                Rp {{ number_format(abs($total)) }}
            </p>
        </div>
    </div>
</div>
