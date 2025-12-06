@extends('layouts.app')

@section('content')
@php
$colors = [
    '#EF4444', '#F97316', '#F59E0B', '#EAB308', '#84CC16', 
    '#22C55E', '#10B981', '#14B8A6', '#06B6D4', '#0EA5E9',
    '#3B82F6', '#6366F1', '#8B5CF6', '#A855F7', '#D946EF',
    '#EC4899', '#F43F5E'
];
@endphp
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="min-h-screen bg-gray-50 pb-20">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-900">{{ __('stats.title') }}</h1>
                <div class="relative">
                    <select id="periodSelect" class="bg-orange-500 text-white px-3 py-1.5 rounded-lg hover:bg-orange-600 transition-colors appearance-none pr-6 cursor-pointer text-sm">
                        <option value="monthly" {{ request('period', 'monthly') === 'monthly' ? 'selected' : '' }}>{{ __('stats.monthly') }}</option>
                        <option value="weekly" {{ request('period') === 'weekly' ? 'selected' : '' }}>{{ __('stats.weekly') }}</option>
                        <option value="annually" {{ request('period') === 'annually' ? 'selected' : '' }}>{{ __('stats.annually') }}</option>
                    </select>
                    <svg class="w-3 h-3 absolute right-1.5 top-1/2 transform -translate-y-1/2 text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Content -->
    <div class="px-4 py-6">
        
        <!-- Period Navigation -->
        <div class="flex items-center justify-center mb-6">
            @if($period === 'weekly')
                <a href="{{ route('stats', ['period' => 'weekly', 'week' => $prevWeek, 'year' => $prevYear]) }}" 
                   class="p-2 text-gray-600 hover:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                
                <div class="text-center mx-4">
                    <div class="text-lg font-semibold text-gray-900">{{ __('stats.week', ['week' => $week, 'year' => $year]) }}</div>
                </div>
                
                <a href="{{ route('stats', ['period' => 'weekly', 'week' => $nextWeek, 'year' => $nextYear]) }}" 
                   class="p-2 text-gray-600 hover:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            @elseif($period === 'annually')
                <a href="{{ route('stats', ['period' => 'annually', 'year' => $prevYear]) }}" 
                   class="p-2 text-gray-600 hover:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                
                <div class="text-center mx-4">
                    <div class="text-lg font-semibold text-gray-900">{{ __('stats.year', ['year' => $year]) }}</div>
                </div>
                
                <a href="{{ route('stats', ['period' => 'annually', 'year' => $nextYear]) }}" 
                   class="p-2 text-gray-600 hover:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            @else
                <a href="{{ route('stats', ['period' => 'monthly', 'month' => $prevMonth, 'year' => $prevYear]) }}" 
                   class="p-2 text-gray-600 hover:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                
                <div class="text-center mx-4">
                    <div class="text-lg font-semibold text-gray-900">{{ date('M Y', mktime(0, 0, 0, $month, 1, $year)) }}</div>
                    <div class="text-xs text-gray-500">{{ $month == date('n') && $year == date('Y') ? __('common.this_month') : '' }}</div>
                </div>
                
                <a href="{{ route('stats', ['period' => 'monthly', 'month' => $nextMonth, 'year' => $nextYear]) }}" 
                   class="p-2 text-gray-600 hover:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            @endif
        </div>

    <!-- Summary Cards -->
    <div class="p-4 grid grid-cols-2 gap-4">
        <div class="bg-white rounded-lg p-4 text-center">
            <div class="text-lg sm:text-xl md:text-2xl font-bold text-green-600">Rp {{ number_format($totalIncome) }}</div>
            <div class="text-xs sm:text-sm text-gray-500">{{ __('stats.income') }}</div>
        </div>
        <div class="bg-white rounded-lg p-4 text-center">
            <div class="text-lg sm:text-xl md:text-2xl font-bold text-red-600">Rp {{ number_format($totalExpense) }}</div>
            <div class="text-xs sm:text-sm text-gray-500">{{ __('stats.expense') }}</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="px-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100">
            <!-- Tab Headers -->
            <div class="flex border-b border-gray-200">
                <button onclick="switchTab('expense')" 
                        id="tab-expense" 
                        class="flex-1 py-3 px-4 text-center font-medium text-orange-600 border-b-2 border-orange-500 bg-orange-50">
                    {{ __('stats.expense') }}
                </button>
                <button onclick="switchTab('income')" 
                        id="tab-income" 
                        class="flex-1 py-3 px-4 text-center font-medium text-gray-500 hover:text-gray-700">
                    {{ __('stats.income') }}
                </button>
            </div>

            <!-- Tab Content -->
            <div class="p-4">
                <!-- Expense Tab -->
                <div id="content-expense" class="tab-content">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Expense Pie Chart -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('stats.expense_by_category') }}</h3>
                            @if($expenseData->count() > 0)
                                <div class="relative h-64">
                                    <canvas id="expenseChart"></canvas>
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('stats.no_expenses') }}</h3>
                                    <p class="text-gray-500 mb-4">{{ __('stats.no_expense_transactions') }}</p>
                                    <a href="{{ route('transaction.new') }}" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors">
                                        {{ __('stats.add_expense') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Expense Summary -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('stats.expense_summary') }}</h3>
                            @if($expenseData->count() > 0)
                                @php
                                    $totalExpense = $expenseData->sum('amount');
                                @endphp
                                <div class="space-y-3">
                                    @foreach($expenseData->sortByDesc('amount') as $index => $category)
                                    @php
                                        $percentage = $totalExpense > 0 ? round(($category['amount'] / $totalExpense) * 100, 1) : 0;
                                        $colorIndex = $index % count($colors);
                                    @endphp
                                    <div class="p-3 bg-white rounded-lg">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: {{ $colors[$colorIndex] }}20;">
                                                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $colors[$colorIndex] }};"></div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $category['category_name'] ?? __('stats.no_category') }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ __('stats.percentage', ['percentage' => $percentage]) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-red-600">
                                                    Rp {{ number_format($category['amount']) }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        @if($category['budget'])
                                            @php
                                                $budget = $category['budget'];
                                                $progressColor = $budget['is_over_budget'] ? 'bg-red-500' : 
                                                              ($budget['percentage'] > 80 ? 'bg-yellow-500' : 'bg-green-500');
                                            @endphp
                                            <div class="mt-2 p-2 bg-gray-50 rounded-lg">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs text-gray-600">{{ __('stats.budget') }}</span>
                                                    <span class="text-xs font-medium {{ $budget['is_over_budget'] ? 'text-red-600' : 'text-gray-900' }}">
                                                        Rp {{ number_format($budget['spent']) }} / Rp {{ number_format($budget['amount']) }}
                                                    </span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 {{ $progressColor }} rounded-full transition-all duration-300" 
                                                         style="width: {{ min($budget['percentage'], 100) }}%"></div>
                                                </div>
                                                <div class="flex items-center justify-between mt-1">
                                                    <span class="text-xs text-gray-500">
                                                        {{ $budget['percentage'] }}% {{ __('stats.used') }}
                                                    </span>
                                                    @if($budget['remaining'] > 0)
                                                        <span class="text-xs text-green-600">
                                                            {{ __('stats.remaining') }}: Rp {{ number_format($budget['remaining']) }}
                                                        </span>
                                                    @elseif($budget['is_over_budget'])
                                                        <span class="text-xs text-red-600">
                                                            {{ __('stats.over_budget') }}: Rp {{ number_format(abs($budget['remaining'])) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="mt-2 p-2 bg-gray-50 rounded-lg">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-gray-500">{{ __('stats.no_budget_set') }}</span>
                                                    @if($category['category_id'])
                                                        <a href="{{ route('budgets.create', ['category_id' => $category['category_id']]) }}" 
                                                           class="text-xs text-orange-600 hover:text-orange-700 font-medium">
                                                            {{ __('stats.set_budget') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <p class="text-gray-500 text-sm">{{ __('stats.no_expenses') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Income Tab -->
                <div id="content-income" class="tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Income Pie Chart -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('stats.income_by_category') }}</h3>
                            @if($incomeData->count() > 0)
                                <div class="relative h-64">
                                    <canvas id="incomeChart"></canvas>
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('stats.no_income') }}</h3>
                                    <p class="text-gray-500 mb-4">{{ __('stats.no_income_transactions') }}</p>
                                    <a href="{{ route('transaction.new') }}" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors">
                                        {{ __('stats.add_income') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Income Summary -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('stats.income_summary') }}</h3>
                            @if($incomeData->count() > 0)
                                @php
                                    $totalIncome = $incomeData->sum('amount');
                                @endphp
                                <div class="space-y-3">
                                    @foreach($incomeData->sortByDesc('amount') as $index => $category)
                                    @php
                                        $percentage = $totalIncome > 0 ? round(($category['amount'] / $totalIncome) * 100, 1) : 0;
                                        $colorIndex = $index % count($colors);
                                    @endphp
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg">
                                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: {{ $colors[$colorIndex] }}20;">
                                                <div class="w-4 h-4 rounded-full" style="background-color: {{ $colors[$colorIndex] }};"></div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $category['category_name'] ?? __('stats.no_category') }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ __('stats.percentage', ['percentage' => $percentage]) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-bold text-green-600">
                                                Rp {{ number_format($category['amount']) }}
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <p class="text-gray-500 text-sm">{{ __('stats.no_income') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart colors
const colors = [
    '#EF4444', '#F97316', '#F59E0B', '#EAB308', '#84CC16', 
    '#22C55E', '#10B981', '#14B8A6', '#06B6D4', '#0EA5E9',
    '#3B82F6', '#6366F1', '#8B5CF6', '#A855F7', '#D946EF',
    '#EC4899', '#F43F5E'
];

// Chart data from PHP
const expenseData = @json($expenseData);
const incomeData = @json($incomeData);

let expenseChart = null;
let incomeChart = null;

function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active state from all tabs
    document.querySelectorAll('[id^="tab-"]').forEach(tab => {
        tab.classList.remove('text-orange-600', 'border-b-2', 'border-orange-500', 'bg-orange-50');
        tab.classList.add('text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Add active state to selected tab
    const activeTab = document.getElementById('tab-' + tabName);
    activeTab.classList.remove('text-gray-500');
    activeTab.classList.add('text-orange-600', 'border-b-2', 'border-orange-500', 'bg-orange-50');
    
    // Initialize charts when tab is shown
    if (tabName === 'expense' && expenseData.length > 0) {
        initExpenseChart();
    } else if (tabName === 'income' && incomeData.length > 0) {
        initIncomeChart();
    }
}

function initExpenseChart() {
    if (expenseChart) {
        expenseChart.destroy();
    }
    
    const ctx = document.getElementById('expenseChart').getContext('2d');
    expenseChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: expenseData.map(item => item.category_name),
            datasets: [{
                data: expenseData.map(item => item.amount),
                backgroundColor: colors.slice(0, expenseData.length),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ': Rp ' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                },
                datalabels: {
                    display: true,
                    color: '#374151',
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    formatter: function(value, context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return percentage + '%';
                    }
                }
            },
            layout: {
                padding: 0
            }
        },
        plugins: []
    });
}

function initIncomeChart() {
    if (incomeChart) {
        incomeChart.destroy();
    }
    
    const ctx = document.getElementById('incomeChart').getContext('2d');
    incomeChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: incomeData.map(item => item.category_name),
            datasets: [{
                data: incomeData.map(item => item.amount),
                backgroundColor: colors.slice(0, incomeData.length),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ': Rp ' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                },
                datalabels: {
                    display: true,
                    color: '#374151',
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    formatter: function(value, context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return percentage + '%';
                    }
                }
            },
            layout: {
                padding: 0
            }
        },
        plugins: []
    });
}

// Handle period selection
document.getElementById('periodSelect').addEventListener('change', function() {
    const period = this.value;
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('period', period);
    
    // Reset month/year to current when changing period
    if (period === 'weekly') {
        const now = new Date();
        // Get week number
        const start = new Date(now.getFullYear(), 0, 1);
        const days = Math.floor((now - start) / (24 * 60 * 60 * 1000));
        const weekNumber = Math.ceil((days + start.getDay() + 1) / 7);
        currentUrl.searchParams.set('week', weekNumber);
        currentUrl.searchParams.set('year', now.getFullYear());
    } else if (period === 'annually') {
        const now = new Date();
        currentUrl.searchParams.set('year', now.getFullYear());
        currentUrl.searchParams.delete('month');
        currentUrl.searchParams.delete('week');
    } else {
        const now = new Date();
        currentUrl.searchParams.set('month', now.getMonth() + 1);
        currentUrl.searchParams.set('year', now.getFullYear());
        currentUrl.searchParams.delete('week');
    }
    
    window.location.href = currentUrl.toString();
});

// Initialize expense chart on page load (default tab)
document.addEventListener('DOMContentLoaded', function() {
    if (expenseData.length > 0) {
        initExpenseChart();
    }
});
</script>
@endsection
