@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 py-3">
            <div class="flex items-center space-x-4">
                <a href="{{ route('transactions') }}" class="text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h1 class="text-lg font-semibold text-gray-900">{{ __('transactions.edit_transaction') }}</h1>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="px-4 py-6">
    <!-- Transaction Type Tabs -->
    <div class="bg-white rounded-xl p-1 shadow-sm border border-gray-100 mb-6">
        <div class="flex space-x-1">
            <button id="income-tab" class="flex-1 py-3 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 rounded-lg transition-colors {{ $transaction->type === 'income' ? 'text-green-500 bg-green-50' : '' }}" onclick="switchTab('income')">
                {{ __('transactions.income') }}
            </button>
            <button id="expense-tab" class="flex-1 py-3 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 rounded-lg transition-colors {{ $transaction->type === 'expense' ? 'text-orange-500 bg-orange-50' : '' }}" onclick="switchTab('expense')">
                {{ __('transactions.expense') }}
            </button>
            <button id="transfer-tab" class="flex-1 py-3 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 rounded-lg transition-colors {{ $transaction->type === 'transfer' ? 'text-blue-500 bg-blue-50' : '' }}" onclick="switchTab('transfer')">
                {{ __('transactions.transfer') }}
            </button>
        </div>
    </div>

    <!-- Transaction Form -->
    <form id="transaction-form" class="space-y-6" enctype="multipart/form-data" action="{{ route('transactions.update', $transaction) }}" method="POST">
        @csrf
        @method('PUT')
        
        <!-- Hidden input for transaction type -->
        <input type="hidden" name="type" id="transaction-type" value="{{ $transaction->type }}">
        
        <!-- 1. Date and Time -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">{{ __('transactions.date') }} & {{ __('transactions.time') }}</label>
                <button type="button" onclick="refreshDateTime()" 
                        class="text-xs text-orange-600 hover:text-orange-700 flex items-center space-x-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>{{ __('transactions.update_time') }}</span>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input type="date" name="transaction_date" id="date-input" required
                       value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                <input type="time" name="transaction_time" id="time-input"
                       value="{{ old('transaction_time', $transaction->transaction_time ? $transaction->transaction_time->format('H:i') : '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ __('transactions.timezone_note') }}</p>
        </div>

        <!-- 2. Account Selection -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('transactions.account') }}</label>
            <select name="account_id" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                <option value="">{{ __('transactions.select_account') }}</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ old('account_id', $transaction->account_id) == $account->id ? 'selected' : '' }}>
                        {{ $account->name }} ({{ $account->type_display_name }}) - Rp {{ number_format($account->balance) }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Transfer Account (only for transfer type) -->
        <div id="transfer-account-section" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 {{ $transaction->type === 'transfer' ? '' : 'hidden' }}">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('transactions.transfer_to') }}</label>
            <select name="transfer_account_id"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                <option value="">{{ __('transactions.select_transfer_account') }}</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ old('transfer_account_id', $transaction->transfer_account_id) == $account->id ? 'selected' : '' }}>
                        {{ $account->name }} ({{ $account->type_display_name }}) - Rp {{ number_format($account->balance) }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- 3. Category Selection -->
        <div id="category-section" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 {{ $transaction->type === 'transfer' ? 'hidden' : '' }}">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('transactions.category') }}</label>
            
            <!-- Category Selection -->
            <div class="space-y-3">
                <!-- Existing Categories Dropdown -->
                <select name="category_id" id="category-select" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">{{ __('transactions.select_category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" data-type="{{ $category->type }}" {{ old('category_id', $transaction->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 4. Amount Input -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('transactions.amount') }}</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                <input type="number" name="amount" min="0" required
                       value="{{ old('amount', number_format($transaction->amount, 0, '.', '')) }}"
                       class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-lg font-medium"
                       placeholder="0">
            </div>
        </div>

        <!-- 5. Description -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('transactions.description') }}</label>
            <input type="text" name="description" required
                   value="{{ old('description', $transaction->description) }}"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                   placeholder="{{ __('transactions.description_placeholder') }}">
        </div>

        <!-- 6. Notes & Picture (Side by Side) -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('transactions.notes') }} ({{ __('transactions.optional') }})</label>
                    <textarea name="notes" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                              placeholder="{{ __('transactions.notes_placeholder') }}">{{ old('notes', $transaction->notes) }}</textarea>
                </div>
                
                <!-- Picture Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('transactions.picture') }} ({{ __('transactions.optional') }})</label>
                    <div class="space-y-3">
                        <!-- Current Picture -->
                        @if($transaction->picture)
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-sm text-gray-600 mb-2">{{ __('transactions.current_picture') }}:</p>
                            <div class="relative">
                                <img src="{{ Storage::url($transaction->picture) }}" 
                                     alt="{{ __('transactions.current_picture') }}" 
                                     class="w-full h-32 object-cover rounded-lg">
                                <button type="button" onclick="removeCurrentImage()" 
                                        class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endif
                        
                        <!-- File Input -->
                        <input type="file" name="picture" id="picture-input" accept="image/*" 
                               class="hidden" onchange="previewImage(this)">
                        
                        <!-- Upload Button -->
                        <button type="button" onclick="document.getElementById('picture-input').click()"
                                class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-orange-400 hover:bg-orange-50 transition-colors text-center">
                            <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p class="text-sm text-gray-600">{{ __('transactions.click_to_upload') }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ __('transactions.picture_format') }}</p>
                        </button>
                        
                        <!-- Image Preview -->
                        <div id="image-preview" class="hidden">
                            <div class="relative">
                                <img id="preview-img" src="" alt="Preview" 
                                     class="w-full h-32 object-cover rounded-lg">
                                <button type="button" onclick="removeImage()" 
                                        class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ __('transactions.picture_save_note') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button - Moved to bottom -->
        <div class="pt-4">
            <div class="flex space-x-3">
                <button type="submit" 
                        class="flex-1 bg-orange-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-orange-600 transition-colors">
                    {{ __('transactions.save_changes') }}
                </button>
                <a href="{{ route('transactions') }}" 
                   class="px-4 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    {{ __('transactions.cancel') }}
                </a>
            </div>
        </div>
    </form>
    </div>
</div>

<script>
// Categories data for quick selection
const categories = @json($categories);
const currentTab = '{{ $transaction->type }}'; // Current transaction type

// Tab switching functionality
function switchTab(type) {
    // Update hidden input
    document.getElementById('transaction-type').value = type;
    
    // Update tab appearance
    document.querySelectorAll('[id$="-tab"]').forEach(tab => {
        tab.classList.remove('text-green-500', 'bg-green-50', 'text-orange-500', 'bg-orange-50', 'text-blue-500', 'bg-blue-50');
        tab.classList.add('text-gray-500');
    });
    
    const activeTab = document.getElementById(type + '-tab');
    if (type === 'income') {
        activeTab.classList.remove('text-gray-500');
        activeTab.classList.add('text-green-500', 'bg-green-50');
    } else if (type === 'expense') {
        activeTab.classList.remove('text-gray-500');
        activeTab.classList.add('text-orange-500', 'bg-orange-50');
    } else if (type === 'transfer') {
        activeTab.classList.remove('text-gray-500');
        activeTab.classList.add('text-blue-500', 'bg-blue-50');
    }
    
    // Show/hide sections based on type
    const categorySection = document.getElementById('category-section');
    const transferAccountSection = document.getElementById('transfer-account-section');
    
    if (type === 'transfer') {
        categorySection.classList.add('hidden');
        transferAccountSection.classList.remove('hidden');
    } else {
        categorySection.classList.remove('hidden');
        transferAccountSection.classList.add('hidden');
    }
    
    // Filter categories based on type
    filterCategories(type);
}

// Filter categories based on transaction type
function filterCategories(type) {
    const categorySelect = document.getElementById('category-select');
    const allOptions = categorySelect.querySelectorAll('option');
    
    // Show/hide options based on type
    allOptions.forEach(option => {
        if (option.value === '') {
            // Always show placeholder option
            option.style.display = 'block';
        } else {
            const optionType = option.getAttribute('data-type');
            if (optionType === type) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        }
    });
    
    // Debug log
    console.log('Filtering categories for type:', type);
    console.log('Available options after filtering:', Array.from(allOptions).filter(opt => opt.style.display !== 'none').map(opt => ({value: opt.value, text: opt.textContent, type: opt.getAttribute('data-type')})));
}

// Refresh date and time
function refreshDateTime() {
    const now = new Date();
    const dateInput = document.getElementById('date-input');
    const timeInput = document.getElementById('time-input');
    
    dateInput.value = now.toISOString().split('T')[0];
    timeInput.value = now.toTimeString().slice(0, 5);
}

// Image preview functionality
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Remove image preview
function removeImage() {
    document.getElementById('picture-input').value = '';
    document.getElementById('image-preview').classList.add('hidden');
}

// Remove current image
function removeCurrentImage() {
    if (confirm('{{ __("transactions.confirm_remove_picture") }}')) {
        // Add hidden input to indicate removal
        const form = document.getElementById('transaction-form');
        const removeInput = document.createElement('input');
        removeInput.type = 'hidden';
        removeInput.name = 'remove_picture';
        removeInput.value = '1';
        form.appendChild(removeInput);
        
        // Hide current image section
        document.querySelector('.bg-gray-50').style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing edit transaction form...');
    console.log('Current tab:', currentTab);
    console.log('Categories data:', categories);
    
    // Set initial tab state
    switchTab(currentTab);
    
    // Set initial category filter
    filterCategories(currentTab);
    
    console.log('Edit transaction form initialized');
});
</script>
@endsection