@props(['date', 'income', 'expense'])

<div class="flex items-center space-x-3">
    <div class="text-xl font-bold text-gray-900">{{ date('j', strtotime($date)) }}</div>
    <div class="flex-1">
        <div class="text-sm text-gray-500">{{ date('Y/m', strtotime($date)) }}</div>
        <div class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full inline-block">{{ date('D', strtotime($date)) }}</div>
    </div>
    <div class="text-right">
        <div class="text-xs text-gray-500">{{ __('transactions.income') }}: Rp {{ number_format($income) }}</div>
        <div class="text-xs text-gray-500">{{ __('transactions.expense') }}: Rp {{ number_format($expense) }}</div>
    </div>
</div>
