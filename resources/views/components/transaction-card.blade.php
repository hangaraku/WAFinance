@props(['transaction'])

<div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
    <div class="flex items-start space-x-3 flex-1 min-w-0">
        <div class="w-20 flex-shrink-0">
            @if($transaction->category)
                <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">{{ $transaction->category->name }}</span>
            @elseif($transaction->type === 'transfer')
                <span class="text-xs text-blue-500 bg-blue-100 px-2 py-1 rounded-full">Transfer</span>
            @else
                <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">No Category</span>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-gray-900 truncate">{{ Str::limit($transaction->description, 25) }}</div>
            @if($transaction->notes)
            <div class="text-xs text-gray-500 mt-1">{{ $transaction->notes }}</div>
            @endif
        </div>
    </div>
    <div class="w-24 text-right flex-shrink-0 ml-2">
        <div class="text-sm font-bold {{ $transaction->type == 'income' ? 'text-green-600' : 'text-red-600' }}">
            Rp {{ number_format($transaction->amount) }}
        </div>
    </div>
</div>
