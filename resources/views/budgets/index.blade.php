@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">{{ __('common.monthly_budget') }}</h1>
                    <p class="text-sm text-gray-600">{{ __('common.manage_monthly_budget') }}</p>
                </div>
                <a href="{{ route('budgets.create') }}" 
                   class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors">
                  
                    {{ __('common.add_budget') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Monthly Summary -->
    <div class="px-4 py-6">
        
        @if($remainingBudget < 0)
        <!-- Global Over Budget Alert -->
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-red-800">Over Budget Alert!</h3>
                    <p class="text-sm text-red-700 mt-1">
                        Total pengeluaran Anda melebihi budget sebesar <strong>Rp {{ number_format(abs($remainingBudget)) }}</strong> 
                        ({{ $totalBudget > 0 ? round((abs($remainingBudget) / $totalBudget) * 100, 1) : 0 }}% lebih dari total budget).
                    </p>
                </div>
            </div>
        </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Budget -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Total Budget</h3>
                    <div class="text-3xl font-bold text-blue-600">
                        Rp {{ number_format($totalBudget) }}
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Bulan {{ $currentMonth }}/{{ $currentYear }}</p>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Total Terpakai</h3>
                    <div class="text-3xl font-bold {{ $totalSpent > $totalBudget ? 'text-red-600' : 'text-red-600' }}">
                        Rp {{ number_format($totalSpent) }}
                    </div>
                    <p class="text-sm {{ $totalSpent > $totalBudget ? 'text-red-600 font-semibold' : 'text-gray-600' }} mt-2">
                        @php
                            $totalPercentage = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;
                        @endphp
                        {{ round($totalPercentage, 1) }}% dari budget
                        @if($totalSpent > $totalBudget)
                            <span class="block text-xs mt-1">(Over Budget!)</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Remaining Budget -->
            <div class="bg-white rounded-xl shadow-sm border {{ $remainingBudget < 0 ? 'border-red-200 bg-red-50' : 'border-gray-100' }} p-6">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Sisa Budget</h3>
                    <div class="text-3xl font-bold {{ $remainingBudget >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        Rp {{ number_format($remainingBudget) }}
                    </div>
                    <p class="text-sm {{ $remainingBudget < 0 ? 'text-red-600 font-semibold' : 'text-gray-600' }} mt-2">
                        @if($remainingBudget >= 0)
                            Masih tersisa
                        @else
                            Melebihi budget sebesar Rp {{ number_format(abs($remainingBudget)) }}
                        @endif
                    </p>
                    @if($remainingBudget < 0)
                        <div class="mt-2 flex items-center justify-center space-x-1">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <span class="text-xs text-red-600 font-medium">Over Budget!</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Budgets List -->
        <div class="space-y-4">
            @forelse($budgets as $budget)
            @php
                $spent = \App\Models\Transaction::where('user_id', auth()->id())
                    ->where('category_id', $budget->category_id)
                    ->where('type', 'expense')
                    ->whereYear('transaction_date', $currentYear)
                    ->whereMonth('transaction_date', $currentMonth)
                    ->sum('amount');
                $percentage = $budget->amount > 0 ? ($spent / $budget->amount) * 100 : 0;
                $isOverBudget = $spent > $budget->amount;
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $budget->category->name }}</h3>
                            <p class="text-sm text-gray-600">Budget: Rp {{ number_format($budget->amount) }}</p>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <div class="text-sm text-gray-600">Terpakai</div>
                        <div class="text-lg font-bold {{ $isOverBudget ? 'text-red-600' : 'text-red-600' }}">
                            Rp {{ number_format($spent) }}
                        </div>
                        <div class="text-xs {{ $isOverBudget ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                            {{ round($percentage, 1) }}%
                            @if($isOverBudget)
                                <span class="ml-1">(Over Budget!)</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">Progress</span>
                        <span class="{{ $isOverBudget ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                            {{ round($percentage, 1) }}%
                            @if($isOverBudget)
                                <span class="ml-1">(Over Budget!)</span>
                            @endif
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 relative overflow-hidden">
                        @if($isOverBudget)
                            <!-- Over budget: Show full bar in red with warning indicator -->
                            <div class="bg-red-500 h-3 rounded-full transition-all duration-300" 
                                 style="width: 100%"></div>
                            <!-- Warning pattern overlay -->
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-20 animate-pulse"></div>
                        @else
                            <!-- Normal progress bar -->
                            <div class="h-3 rounded-full transition-all duration-300 {{ $percentage > 80 ? 'bg-yellow-500' : ($percentage > 100 ? 'bg-red-500' : 'bg-green-500') }}" 
                                 style="width: {{ min($percentage, 100) }}%"></div>
                        @endif
                    </div>
                    
                    @if($isOverBudget)
                        <!-- Over budget warning message -->
                        <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-xs text-red-700 font-medium">
                                    Over budget sebesar Rp {{ number_format($spent - $budget->amount) }} 
                                    ({{ round(($spent - $budget->amount) / $budget->amount * 100, 1) }}% lebih dari budget)
                                </span>
                            </div>
                        </div>
                    @elseif($percentage > 80)
                        <!-- Warning when close to budget limit -->
                        <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-xs text-yellow-700 font-medium">
                                    Hampir mencapai limit budget! Sisa Rp {{ number_format($budget->amount - $spent) }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
                
                <!-- Budget Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="flex space-x-2">
                        <a href="{{ route('budgets.edit', $budget) }}" 
                           class="text-sm text-gray-600 hover:text-gray-800">Edit</a>
                    </div>
                    
                    <div class="flex space-x-2">
                        <form action="{{ route('budgets.destroy', $budget) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800" 
                                    onclick="return confirm('Yakin ingin menghapus budget ini?')">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada budget</h3>
                <p class="text-gray-600 mb-4">Mulai dengan membuat budget untuk kategori pengeluaran Anda.</p>
                <a href="{{ route('budgets.create') }}" 
                   class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition-colors">
                    Buat Budget Pertama
                </a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
