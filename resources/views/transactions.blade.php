@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 py-3">
            <h1 class="text-lg font-semibold text-gray-900">{{ __('transactions.title') }}</h1>
        </div>
    </div>

    <!-- Content -->
    <div class="px-4 py-6">

    <!-- Month Navigation -->
    <x-month-navigation 
        :current-month="$month" 
        :current-year="$year"
        :prev-month="$prevMonth"
        :prev-year="$prevYear"
        :next-month="$nextMonth"
        :next-year="$nextYear"
    />

    <!-- Monthly Summary -->
    <div class="grid grid-cols-3 gap-2 mb-4">
        <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-100">
            <div class="text-center">
                <p class="text-xs text-gray-500 mb-1">{{ __('transactions.income') }}</p>
                <p class="text-sm font-bold text-blue-600"> {{ number_format($monthlySummary['income']) }}</p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-100">
            <div class="text-center">
                <p class="text-xs text-gray-500 mb-1">{{ __('transactions.expense') }}</p>
                <p class="text-sm font-bold text-red-600"> {{ number_format($monthlySummary['expenses']) }}</p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-100">
            <div class="text-center">
                <p class="text-xs text-gray-500 mb-1">{{ __('transactions.total') }}</p>
                <p class="text-sm font-bold {{ $monthlySummary['total'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format(abs($monthlySummary['total'])) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white rounded-xl p-1 shadow-sm border border-gray-100 mb-6">
        <div class="flex justify-center space-x-1 overflow-x-auto scrollbar-hide">
            <button id="tab-harian" class="tab-button flex-shrink-0 py-2 px-4 text-sm font-medium text-orange-500 bg-orange-50 rounded-lg whitespace-nowrap" onclick="switchTab('harian')">
                {{ __('transactions.daily') }}
            </button>
            <button id="tab-kalender" class="tab-button flex-shrink-0 py-2 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap" onclick="switchTab('kalender')">
                {{ __('transactions.calendar') }}
            </button>
            <button id="tab-mingguan" class="tab-button flex-shrink-0 py-2 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap" onclick="switchTab('mingguan')">
                {{ __('transactions.weekly') }}
            </button>
        </div>
    </div>

    <!-- Harian Tab -->
    <div id="content-harian" class="tab-content">
        <div class="space-y-4">
        @if($groupedTransactions->count() > 0)
            @foreach($groupedTransactions as $date => $dayTransactions)
            <div class="space-y-2">
                <div class="flex items-center space-x-3">
                    <div class="text-xl font-bold text-gray-900">{{ date('j', strtotime($date)) }}</div>
                    <div class="flex-1">
                        <div class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full inline-block">{{ date('D', strtotime($date)) }}</div>

                        <div class="text-sm text-gray-500">{{ date('m/Y', strtotime($date)) }}</div>
                    </div>
                    <div class="text-right">
                        @php
                            $dayIncome = $dayTransactions->where('type', 'income')->sum('amount');
                            $dayExpense = $dayTransactions->where('type', 'expense')->sum('amount');
                        @endphp
                        <div class="text-xs text-gray-500">{{ __('transactions.income') }}: Rp {{ number_format($dayIncome) }}</div>
                        <div class="text-xs text-gray-500">{{ __('transactions.expense') }}: Rp {{ number_format($dayExpense) }}</div>
                    </div>
                </div>
                
                @foreach($dayTransactions as $transaction)
                <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                 
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    @if($transaction->category)
                                        <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">{{ $transaction->category->name }}</span>
                                    @elseif($transaction->type === 'transfer')
                                        <span class="text-xs text-blue-500 bg-blue-100 px-2 py-1 rounded-full">{{ __('transactions.transfer') }}</span>
                                    @else
                                        <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">{{ __('transactions.no_category') }}</span>
                                    @endif
                                    
                                    <a href="{{ route('transactions.edit', $transaction) }}" class="text-sm font-medium text-gray-900 whitespace-normal break-words hover:text-orange-600 transition-colors">
                                        {{ $transaction->description }}
                                    </a>
                                </div>
                                
                                @if($transaction->type === 'transfer')
                                    <div class="text-xs text-gray-600 mt-1">
                                        <div>
                                            {{ __('transactions.from') }}: {{ $transaction->account->name ?? __('transactions.unknown_account') }} → {{ __('transactions.to') }}: {{ $transaction->transferAccount->name ?? __('transactions.unknown_account') }}
                                        </div>
                                    </div>
                                @endif
                                
                                @if($transaction->notes)
                                <div class="text-xs text-gray-500 mt-1">{{ $transaction->notes }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-2">
                            <div class="text-sm font-bold 
                                {{ $transaction->type == 'income' ? 'text-green-600' : ($transaction->type == 'expense' ? 'text-red-600' : 'text-black') }}">
                                @if($transaction->type === 'transfer')
                                {{ number_format($transaction->amount) }}
                                @else
                                    {{ $transaction->type == 'income' ? '+' : '-' }} {{ number_format($transaction->amount) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('transactions.no_transactions') }}</h4>
                    <p class="text-gray-500 mb-4">Start by adding your first transaction</p>
                    <a href="{{ route('transaction.new') }}" class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition-colors inline-block">
                        {{ __('transactions.add_transaction') }}
                    </a>
                </div>
            </div>
        @endif
        </div>
    </div>

    <!-- Kalender Tab -->
    <div id="content-kalender" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('transactions.calendar') }}</h3>
            
            @php
                $currentMonth = request('month', date('Y-m'));
                $currentYear = request('year', date('Y'));
                $firstDay = mktime(0, 0, 0, date('m', strtotime($currentMonth)), 1, $currentYear);
                $lastDay = mktime(0, 0, 0, date('m', strtotime($currentMonth)) + 1, 0, $currentYear);
                $daysInMonth = date('t', $firstDay);
                $startDay = date('w', $firstDay); // 0 = Sunday, 1 = Monday, etc.
                
                // Get transactions for current month
                $monthTransactions = $transactions->filter(function($transaction) use ($currentMonth) {
                    return date('Y-m', strtotime($transaction->transaction_date)) === $currentMonth;
                });
                
                // Group by date
                $transactionsByDate = $monthTransactions->groupBy(function($transaction) {
                    return date('Y-m-d', strtotime($transaction->transaction_date));
                });
            @endphp
            
            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-1">
                <!-- Day Headers -->
                <div class="text-center text-xs font-medium text-gray-500 py-2">Sun</div>
                <div class="text-center text-xs font-medium text-gray-500 py-2">Mon</div>
                <div class="text-center text-xs font-medium text-gray-500 py-2">Tue</div>
                <div class="text-center text-xs font-medium text-gray-500 py-2">Wed</div>
                <div class="text-center text-xs font-medium text-gray-500 py-2">Thu</div>
                <div class="text-center text-xs font-medium text-gray-500 py-2">Fri</div>
                <div class="text-center text-xs font-medium text-gray-500 py-2">Sat</div>
                
                <!-- Empty cells for days before month starts -->
                @for($i = 0; $i < $startDay; $i++)
                    <div class="h-20 border border-gray-100 rounded bg-gray-50"></div>
                @endfor
                
                <!-- Days of the month -->
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date = sprintf('%s-%02d', $currentMonth, $day);
                        $dayTransactions = $transactionsByDate->get($date, collect());
                        $dayIncome = $dayTransactions->where('type', 'income')->sum('amount');
                        $dayExpense = $dayTransactions->where('type', 'expense')->sum('amount');
                        $isToday = $date === date('Y-m-d');
                    @endphp
                    <div class="h-20 border border-gray-200 rounded p-1 {{ $isToday ? 'bg-orange-50 border-orange-200' : 'bg-white' }} cursor-pointer hover:bg-gray-50 transition-colors" 
                         onclick="showDayDetails('{{ $date }}', {{ $dayIncome }}, {{ $dayExpense }}, {{ $dayTransactions->count() }})">
                        <div class="text-xs font-medium {{ $isToday ? 'text-orange-600' : 'text-gray-900' }}">{{ $day }}</div>
                        <div class="text-[10px] space-y-0.5 mt-1 leading-tight">
                            <div class="text-green-600 font-medium truncate">{{ number_format($dayIncome) }}</div>
                            <div class="text-red-600 font-medium truncate">{{ number_format($dayExpense) }}</div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Day Details Modal -->
    <div id="day-details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white rounded-t-2xl absolute bottom-0 left-0 right-0 max-h-[80vh] overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 id="modal-date" class="text-lg font-semibold text-gray-900"></h3>
                <button id="close-day-modal" class="p-2 text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="p-4 overflow-y-auto max-h-[60vh]">
                <!-- Summary Cards -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <div class="text-xs text-green-600 mb-1">{{ __('transactions.income') }}</div>
                        <div id="modal-income" class="text-sm font-bold text-green-600">0</div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3 text-center">
                        <div class="text-xs text-red-600 mb-1">{{ __('transactions.expense') }}</div>
                        <div id="modal-expense" class="text-sm font-bold text-red-600">0</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <div class="text-xs text-gray-600 mb-1">{{ __('transactions.total') }}</div>
                        <div id="modal-total" class="text-sm font-bold text-gray-600">0</div>
                    </div>
                </div>
                
                <!-- Transactions List -->
                <div id="modal-transactions" class="space-y-2">
                    <!-- Transactions will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Mingguan Tab -->
    <div id="content-mingguan" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('transactions.weekly') }}</h3>
            
            @php
                // Get current month and year
                $currentMonth = request('month', date('Y-m'));
                $currentYear = request('year', date('Y'));
                
                // Get first day of month
                $firstDay = mktime(0, 0, 0, date('m', strtotime($currentMonth)), 1, $currentYear);
                $lastDay = mktime(0, 0, 0, date('m', strtotime($currentMonth)) + 1, 0, $currentYear);
                $daysInMonth = date('t', $firstDay);
                
                // Calculate weeks
                $weeks = [];
                $currentWeek = 1;
                
                for ($day = 1; $day <= $daysInMonth; $day += 7) {
                    $weekEnd = min($day + 6, $daysInMonth);
                    $weeks[$currentWeek] = [
                        'start' => $day,
                        'end' => $weekEnd,
                        'label' => "Week " . $currentWeek
                    ];
                    $currentWeek++;
                }
                
                // Group transactions by week
                $weeklyData = [];
                foreach ($weeks as $weekNum => $weekInfo) {
                    $weekTransactions = $transactions->filter(function($transaction) use ($currentMonth, $weekInfo) {
                        $transactionDay = (int)date('j', strtotime($transaction->transaction_date));
                        return $transactionDay >= $weekInfo['start'] && $transactionDay <= $weekInfo['end'];
                    });
                    
                    $weeklyData[$weekNum] = [
                        'label' => $weekInfo['label'],
                        'start' => $weekInfo['start'],
                        'end' => $weekInfo['end'],
                        'income' => $weekTransactions->where('type', 'income')->sum('amount'),
                        'expense' => $weekTransactions->where('type', 'expense')->sum('amount'),
                        'transfer' => $weekTransactions->where('type', 'transfer')->sum('amount'),
                        'transactions' => $weekTransactions
                    ];
                }
            @endphp
            
            @if(count($weeklyData) > 0)
                <div class="space-y-4">
                    @foreach($weeklyData as $weekNum => $data)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-semibold text-gray-900">{{ $data['label'] }}</h4>
                                <span class="text-sm text-gray-500">{{ $data['start'] }}-{{ $data['end'] }} {{ date('M', strtotime($currentMonth)) }}</span>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-3 mb-4">
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">{{ __('transactions.income') }}</div>
                                    <div class="text-sm font-bold text-green-600">{{ number_format($data['income']) }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">{{ __('transactions.expense') }}</div>
                                    <div class="text-sm font-bold text-red-600">{{ number_format($data['expense']) }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">{{ __('transactions.transfer') }}</div>
                                    <div class="text-sm font-bold text-blue-600">{{ number_format($data['transfer']) }}</div>
                                </div>
                            </div>
                            
                            <!-- Recent transactions for this week -->
                            <div class="space-y-2">
                                @foreach($data['transactions']->take(3) as $transaction)
                                    <div class="flex items-center justify-between p-2 bg-white rounded border">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $transaction->type === 'income' ? 'bg-green-100' : ($transaction->type === 'expense' ? 'bg-red-100' : 'bg-blue-100') }}">
                                                <svg class="w-4 h-4 {{ $transaction->type === 'income' ? 'text-green-600' : ($transaction->type === 'expense' ? 'text-red-600' : 'text-blue-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    @if($transaction->type === 'income')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                    @elseif($transaction->type === 'expense')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                    @endif
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $transaction->description }}</p>
                                                <p class="text-xs text-gray-500">{{ $transaction->category->name ?? 'No Category' }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium {{ $transaction->type === 'income' ? 'text-green-600' : ($transaction->type === 'expense' ? 'text-red-600' : 'text-blue-600') }}">
                                                {{ $transaction->type === 'income' ? '+' : ($transaction->type === 'expense' ? '-' : '') }}{{ number_format($transaction->amount) }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ date('M j', strtotime($transaction->transaction_date)) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                                
                                @if($data['transactions']->count() > 3)
                                    <div class="text-center">
                                        <button class="text-xs text-orange-500 hover:text-orange-600">
                                            {{ __('transactions.view_all') }} ({{ $data['transactions']->count() - 3 }} {{ __('transactions.more') }})
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('transactions.no_transactions') }}</h4>
                    <p class="text-gray-500 mb-4">Start by adding your first transaction</p>
                    <a href="{{ route('transaction.new') }}" class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition-colors inline-block">
                        {{ __('transactions.add_transaction') }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="fixed bottom-24 right-4">
        <a href="{{ route('transaction.new') }}" class="w-14 h-14 bg-orange-500 text-white rounded-full shadow-lg hover:bg-orange-600 transition-colors flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        </a>
    </div>
</div>

<script>
// Tab switching functionality
function switchTab(tabName) {
    console.log('Switching to tab:', tabName);
    
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active state from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('text-orange-500', 'bg-orange-50');
        button.classList.add('text-gray-500', 'hover:text-gray-700');
    });
    
    // Show selected tab content
    const targetContent = document.getElementById('content-' + tabName);
    console.log('Target content element:', targetContent);
    if (targetContent) {
        targetContent.classList.remove('hidden');
    } else {
        console.error('Content element not found for tab:', tabName);
    }
    
    // Add active state to selected tab button
    const activeButton = document.getElementById('tab-' + tabName);
    console.log('Active button element:', activeButton);
    if (activeButton) {
        activeButton.classList.remove('text-gray-500', 'hover:text-gray-700');
        activeButton.classList.add('text-orange-500', 'bg-orange-50');
    } else {
        console.error('Button element not found for tab:', tabName);
    }
}

// Day details modal functionality
function showDayDetails(date, income, expense, transactionCount) {
    console.log('Showing day details for:', date, 'Income:', income, 'Expense:', expense);
    
    // Format date for display
    const dateObj = new Date(date);
    const formattedDate = dateObj.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Update modal content
    document.getElementById('modal-date').textContent = formattedDate;
    document.getElementById('modal-income').textContent = new Intl.NumberFormat().format(income);
    document.getElementById('modal-expense').textContent = new Intl.NumberFormat().format(expense);
    
    const total = income - expense;
    const totalElement = document.getElementById('modal-total');
    totalElement.textContent = new Intl.NumberFormat().format(Math.abs(total));
    totalElement.className = total >= 0 ? 'text-sm font-bold text-green-600' : 'text-sm font-bold text-red-600';
    
    // Get transactions for this date
    const dayTransactions = @json($transactionsByDate ?? []);
    const transactions = dayTransactions[date] || [];
    
    // Populate transactions list
    const transactionsContainer = document.getElementById('modal-transactions');
    if (transactions.length > 0) {
        transactionsContainer.innerHTML = transactions.map(transaction => `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center ${transaction.type === 'income' ? 'bg-green-100' : (transaction.type === 'expense' ? 'bg-red-100' : 'bg-blue-100')}">
                        <svg class="w-4 h-4 ${transaction.type === 'income' ? 'text-green-600' : (transaction.type === 'expense' ? 'text-red-600' : 'text-blue-600')}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            ${transaction.type === 'income' ? 
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>' :
                                transaction.type === 'expense' ?
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>' :
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>'
                            }
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">${transaction.description}</p>
                        <p class="text-xs text-gray-500">${transaction.category ? transaction.category.name : 'No Category'}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium ${transaction.type === 'income' ? 'text-green-600' : (transaction.type === 'expense' ? 'text-red-600' : 'text-blue-600')}">
                        ${transaction.type === 'income' ? '+' : (transaction.type === 'expense' ? '-' : '')}${new Intl.NumberFormat().format(transaction.amount)}
                    </p>
                    <p class="text-xs text-gray-500">${transaction.time || ''}</p>
                </div>
            </div>
        `).join('');
    } else {
        transactionsContainer.innerHTML = `
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p class="text-gray-500">No transactions on this day</p>
            </div>
        `;
    }
    
    // Show modal
    document.getElementById('day-details-modal').classList.remove('hidden');
}

// Close modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('day-details-modal');
    const closeBtn = document.getElementById('close-day-modal');
    
    closeBtn.addEventListener('click', function() {
        modal.classList.add('hidden');
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>
@endsection
