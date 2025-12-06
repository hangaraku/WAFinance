<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('common.app_name') }}</title>
    
    <!-- TailwindCSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Livewire -->
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Top Status Bar (Mobile) -->
    @auth
        <x-top-bar 
            :title="__('common.app_name')" 
            :show-user-avatar="true" 
            :user-name="Auth::user()->name" 
        />
    @endauth

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="fixed top-20 left-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 hover:text-green-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="fixed top-20 left-4 right-4 z-50 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-700 hover:text-red-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Search Modal -->
    <div id="search-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white rounded-t-2xl absolute bottom-0 left-0 right-0 max-h-[80vh] overflow-hidden">
            <!-- Search Header -->
            <div class="flex items-center p-4 border-b border-gray-200">
                <div class="flex-1 relative">
                    <input type="text" id="search-input" placeholder="{{ __('common.search_placeholder') }}" 
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <button id="search-close" class="ml-3 p-2 text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Search Results -->
            <div id="search-results" class="overflow-y-auto max-h-[60vh]">
                <!-- Results will be populated here -->
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <main class="pb-20">
        @yield('content')
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
        <div class="flex justify-around items-end relative">
            <!-- Left side navigation items -->
            <a href="{{ route('transactions') }}" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-orange-500 transition-colors {{ request()->routeIs('transactions*') || request()->routeIs('transaction.*') ? 'text-orange-500' : '' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span class="text-xs">{{ __('common.transactions') }}</span>
            </a>
            
            <a href="{{ route('stats') }}" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-orange-500 transition-colors {{ request()->routeIs('stats') ? 'text-orange-500' : '' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span class="text-xs">{{ __('common.stats') }}</span>
            </a>
            
            <!-- Center AI Button - Special Design -->
            <div class="flex flex-col items-center relative">
                <a href="{{ route('ai.chat') }}" class="relative group">
                    <!-- Main AI Button with orange theme -->
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center shadow-lg transform transition-all duration-300 hover:scale-110 hover:shadow-xl -mt-4 {{ request()->routeIs('ai.*') ? 'ring-4 ring-orange-200' : '' }}">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <!-- Glow effect -->
                    <div class="absolute inset-0 w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-500 rounded-full blur-md opacity-30 -z-10"></div>
                </a>
                <span class="text-xs font-semibold text-gray-700 mt-1 {{ request()->routeIs('ai.*') ? 'text-orange-600' : '' }}">{{ __('common.ai') }}</span>
            </div>
            
            <!-- Right side navigation items -->
            <a href="{{ route('accounts.index') }}" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-orange-500 transition-colors {{ request()->routeIs('accounts.*') ? 'text-orange-500' : '' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span class="text-xs">{{ __('common.accounts') }}</span>
            </a>
            
            <a href="{{ route('settings.index') }}" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-orange-500 transition-colors {{ request()->routeIs('settings.index') ? 'text-orange-500' : '' }}">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                </svg>
                <span class="text-xs">{{ __('common.more') }}</span>
            </a>
        </div>
    </nav>

    <!-- Livewire -->
    @livewireScripts

    <!-- Search Functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchToggle = document.getElementById('search-toggle');
        const searchModal = document.getElementById('search-modal');
        const searchClose = document.getElementById('search-close');
        const searchInput = document.getElementById('search-input');
        const searchResults = document.getElementById('search-results');
        
        let searchTimeout;
        
        // Open search modal
        searchToggle.addEventListener('click', function() {
            searchModal.classList.remove('hidden');
            searchInput.focus();
        });
        
        // Close search modal
        searchClose.addEventListener('click', function() {
            searchModal.classList.add('hidden');
            searchInput.value = '';
            searchResults.innerHTML = '';
        });
        
        // Close modal when clicking outside
        searchModal.addEventListener('click', function(e) {
            if (e.target === searchModal) {
                searchModal.classList.add('hidden');
                searchInput.value = '';
                searchResults.innerHTML = '';
            }
        });
        
        // Search functionality
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchResults.innerHTML = '';
                return;
            }
            
            // Debounce search
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        function performSearch(query) {
            // Show loading state
            searchResults.innerHTML = `
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
                    <span class="ml-2 text-gray-600">Searching...</span>
                </div>
            `;
            
            fetch(`/api/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data, query);
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = `
                        <div class="flex items-center justify-center py-8 text-red-600">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Search failed. Please try again.
                        </div>
                    `;
                });
        }
        
        function displaySearchResults(data, query) {
            let html = '';
            
            // Transactions
            if (data.transactions && data.transactions.length > 0) {
                html += `
                    <div class="px-4 py-2">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Transactions</h3>
                        <div class="space-y-2">
                `;
                
                data.transactions.forEach(transaction => {
                    const typeColor = transaction.type === 'income' ? 'text-green-600' : 
                                    transaction.type === 'expense' ? 'text-red-600' : 'text-blue-600';
                    const typeIcon = transaction.type === 'income' ? 'M12 6v6m0 0v6m0-6h6m-6 0H6' :
                                   transaction.type === 'expense' ? 'M18 12H6' : 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4';
                    
                    html += `
                        <a href="${transaction.url}" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 ${typeColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${typeIcon}"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">${transaction.description}</p>
                                        <p class="text-xs text-gray-500">${transaction.date} • ${transaction.category || 'No category'}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium ${typeColor}">Rp ${formatNumber(transaction.amount)}</p>
                                    <p class="text-xs text-gray-500">${transaction.account}</p>
                                </div>
                            </div>
                        </a>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // Accounts
            if (data.accounts && data.accounts.length > 0) {
                html += `
                    <div class="px-4 py-2">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Accounts</h3>
                        <div class="space-y-2">
                `;
                
                data.accounts.forEach(account => {
                    html += `
                        <a href="${account.url}" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">${account.name}</p>
                                        <p class="text-xs text-gray-500">${account.type}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">Rp ${formatNumber(account.balance)}</p>
                                </div>
                            </div>
                        </a>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // Budgets
            if (data.budgets && data.budgets.length > 0) {
                html += `
                    <div class="px-4 py-2">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Budgets</h3>
                        <div class="space-y-2">
                `;
                
                data.budgets.forEach(budget => {
                    const percentage = budget.amount > 0 ? (budget.spent / budget.amount) * 100 : 0;
                    const progressColor = percentage > 100 ? 'bg-red-500' : percentage > 80 ? 'bg-yellow-500' : 'bg-green-500';
                    
                    html += `
                        <a href="${budget.url}" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">${budget.name}</p>
                                        <p class="text-xs text-gray-500">${budget.category || 'No category'}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">Rp ${formatNumber(budget.spent)} / Rp ${formatNumber(budget.amount)}</p>
                                    <div class="w-16 h-1 bg-gray-200 rounded-full mt-1">
                                        <div class="h-1 ${progressColor} rounded-full" style="width: ${Math.min(percentage, 100)}%"></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // Navigation
            if (data.navigation && data.navigation.length > 0) {
                html += `
                    <div class="px-4 py-2">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Navigation</h3>
                        <div class="space-y-2">
                `;
                
                data.navigation.forEach(item => {
                    html += `
                        <a href="${item.url}" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${item.icon}"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">${item.name}</p>
                                    <p class="text-xs text-gray-500">Go to ${item.name.toLowerCase()}</p>
                                </div>
                            </div>
                        </a>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // No results
            if (!html) {
                html = `
                    <div class="flex flex-col items-center justify-center py-12 text-gray-500">
                        <svg class="w-12 h-12 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-sm">No results found for "${query}"</p>
                        <p class="text-xs text-gray-400 mt-1">Try searching for transactions, accounts, budgets, or navigation items</p>
                    </div>
                `;
            }
            
            searchResults.innerHTML = html;
        }
        
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
    });
    </script>
</body>
</html>