@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-900">{{ __('common.accounts') }}</h1>
                <div class="relative">
                    <button id="accountsMenuBtn" class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                    
                    <!-- Context Menu -->
                    <div id="accountsMenu" class="hidden absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                        <a href="{{ route('accounts.create') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            {{ __('common.add') }} {{ __('common.accounts') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="px-4 py-6">
        <!-- Total Balance Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900">Total Balance</h2>
                <div class="text-4xl font-bold text-green-600 mt-2">
                    Rp {{ number_format($totalBalance) }}
                </div>
                <p class="text-sm text-gray-600 mt-2">{{ $accounts->count() }} active accounts</p>
            </div>
        </div>
    </div>

    <!-- Accounts List -->
    <div class="px-4 pb-6">
        <div class="space-y-6">
            @php
                $groupedAccounts = $accounts->groupBy('type');
                $typeLabels = [
                    'cash' => 'Cash',
                    'bank' => 'Bank Account', 
                    'credit_card' => 'Credit Card',
                    'investment' => 'Investment',
                    'other' => 'Other'
                ];
            @endphp
            
            @forelse($groupedAccounts as $type => $typeAccounts)
                <div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">
                        {{ $typeLabels[$type] ?? ucfirst($type) }} ({{ $typeAccounts->count() }})
                    </h3>
                    <div class="space-y-3">
                        @foreach($typeAccounts as $account)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Account Icon -->
                        <div class="w-12 h-12 rounded-full bg-{{ $account->color }}-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-{{ $account->color }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @switch($account->icon)
                                    @case('banknotes')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        @break
                                    @case('building-library')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                                        @break
                                    @case('credit-card')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        @break
                                    @case('chart-bar')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        @break
                                    @case('wallet')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        @break
                                    @default
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                @endswitch
                            </svg>
                        </div>
                        
                        <!-- Account Info -->
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $account->name }}</h3>
                                @if($account->is_default)
                                    <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full">Default</span>
                                @endif
                                @if(!$account->is_active)
                                    <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">Inactive</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600">{{ $account->type_display_name }}</p>
                            @if($account->description)
                                <p class="text-xs text-gray-500 mt-1">{{ $account->description }}</p>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Account Balance -->
                    <div class="text-right">
                        <div class="text-xl font-bold {{ $account->balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Rp {{ number_format($account->balance) }}
                        </div>
                        <p class="text-xs text-gray-500">{{ $account->total_transactions }} transactions</p>
                    </div>
                </div>
                
                <!-- Account Actions -->
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                    <div class="flex space-x-2">
                        <a href="{{ route('accounts.show', $account) }}" 
                           class="text-sm text-blue-600 hover:text-blue-800">View Details</a>
                        <a href="{{ route('accounts.edit', $account) }}" 
                           class="text-sm text-gray-600 hover:text-gray-800">Edit</a>
                    </div>
                    
                    <div class="flex space-x-2">
                        @if(!$account->is_default)
                            <form action="{{ route('accounts.set-default', $account) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-orange-600 hover:text-orange-800">
                                    Set Default
                                </button>
                            </form>
                        @endif
                        
                        <form action="{{ route('accounts.toggle-active', $account) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm {{ $account->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        
                        @if($account->transactions()->count() == 0)
                            <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800" 
                                        onclick="return confirm('Are you sure you want to delete this account?')">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
                        @endforeach
                    </div>
                </div>
            @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No accounts yet</h3>
                <p class="text-gray-600 mb-4">Get started by creating your first financial account.</p>
                <a href="{{ route('accounts.create') }}" 
                   class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition-colors">
                    Create Account
                </a>
            </div>
            @endforelse
        </div>
    </div>
</div>

<script>
// Context Menu Toggle
document.getElementById('accountsMenuBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    const menu = document.getElementById('accountsMenu');
    menu.classList.toggle('hidden');
});

// Close menu when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('accountsMenu');
    const btn = document.getElementById('accountsMenuBtn');
    
    if (!menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.add('hidden');
    }
});
</script>
@endsection
