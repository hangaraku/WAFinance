@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 py-3">
            <div class="flex items-center space-x-4">
                <a href="{{ route('accounts.index') }}" class="text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h1 class="text-lg font-semibold text-gray-900">{{ $account->name }}</h1>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="px-4 py-6">

        <!-- Account Summary -->
        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Current Balance -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Current Balance</h3>
                        <div class="text-3xl font-bold {{ $account->balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Rp {{ number_format($account->balance) }}
                        </div>
                        <p class="text-sm text-gray-600 mt-2">
                            @if($account->is_default)
                                <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs">Default Account</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full bg-{{ $account->color }}-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-{{ $account->color }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Account Details</h3>
                        <p class="text-sm text-gray-600">{{ $account->type_display_name }}</p>
                        @if($account->description)
                            <p class="text-xs text-gray-500 mt-2">{{ $account->description }}</p>
                        @endif
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Statistics</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Status:</span>
                                <span class="font-medium {{ $account->is_active ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Transactions:</span>
                                <span class="font-medium">{{ $account->transactions()->count() }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Created:</span>
                                <span class="font-medium">{{ $account->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-medium text-gray-900">Recent Transactions</h3>
                    <p class="text-sm text-gray-600">Latest 20 transactions for this account</p>
                </div>
                
                <div class="p-6">
                    @forelse($account->transactions as $transaction)
                    <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="flex items-center space-x-4">
                            <!-- Transaction Type Icon -->
                            <div class="w-10 h-10 rounded-full bg-{{ $transaction->type_color }}-100 flex items-center justify-center">
                                @switch($transaction->type)
                                    @case('income')
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        @break
                                    @case('expense')
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                        @break
                                    @case('transfer')
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                        @break
                                @endswitch
                            </div>
                            
                            <!-- Transaction Details -->
                            <div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('transactions.edit', $transaction) }}" class="font-medium text-gray-900 hover:text-orange-600 transition-colors">{{ $transaction->description }}</a>
                                    @if($transaction->isTransfer())
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Transfer</span>
                                    @endif
                                </div>
                                
                                @if($transaction->isTransfer())
                                    <p class="text-sm text-gray-600">
                                        From: {{ $transaction->account->name }} → To: {{ $transaction->transferAccount->name }}
                                    </p>
                                @else
                                    <p class="text-sm text-gray-600">
                                        @if($transaction->category)
                                            {{ $transaction->category->name }}
                                        @endif
                                        • {{ $transaction->transaction_date->format('M d, Y') }}
                                    </p>
                                @endif
                                
                                @if($transaction->notes)
                                    <p class="text-xs text-gray-500 mt-1">{{ $transaction->notes }}</p>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Amount -->
                        <div class="text-right">
                            <div class="text-lg font-bold {{ $transaction->type === 'income' ? 'text-green-600' : ($transaction->type === 'expense' ? 'text-red-600' : 'text-blue-600') }}">
                                {{ $transaction->display_amount }}
                            </div>
                            <p class="text-xs text-gray-500">{{ $transaction->transaction_time->format('H:i') }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions yet</h3>
                        <p class="text-gray-600">This account doesn't have any transactions yet.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
