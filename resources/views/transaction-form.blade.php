@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 py-3">
            <div class="flex items-center space-x-4">
                <a href="{{ url()->previous() }}" class="text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h1 class="text-lg font-semibold text-gray-900">{{ __('transactions.add_transaction') }}</h1>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="px-4 py-6">
    <!-- Transaction Type Tabs -->
    <div class="bg-white rounded-xl p-1 shadow-sm border border-gray-100 mb-6">
        <div class="flex space-x-1">
            <button id="income-tab" class="flex-1 py-3 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 rounded-lg transition-colors" onclick="switchTab('income')">
                {{ __('transactions.income') }}
            </button>
            <button id="expense-tab" class="flex-1 py-3 px-4 text-sm font-medium text-orange-500 bg-orange-50 rounded-lg transition-colors" onclick="switchTab('expense')">
                {{ __('transactions.expense') }}
            </button>
            <button id="transfer-tab" class="flex-1 py-3 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 rounded-lg transition-colors" onclick="switchTab('transfer')">
                Transfer
            </button>
        </div>
    </div>

    <!-- Transaction Form -->
    <form id="transaction-form" class="space-y-6" enctype="multipart/form-data" action="{{ route('transaction.store') }}" method="POST">
        @csrf
        
        <!-- Hidden input for transaction type -->
        <input type="hidden" name="transaction_type" id="transaction-type" value="expense">
        
        <!-- 1. Date and Time -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">Tanggal & Waktu</label>
                <button type="button" onclick="refreshDateTime()" 
                        class="text-xs text-orange-600 hover:text-orange-700 flex items-center space-x-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Update Waktu</span>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input type="date" name="date" id="date-input" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                <input type="time" name="time" id="time-input"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
            </div>
            <p class="text-xs text-gray-500 mt-2">Waktu otomatis menggunakan timezone lokal Anda</p>
        </div>

        <!-- 2. Account Selection -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">Account</label>
            <select name="account_id" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                <option value="">Pilih Account</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ $account->is_default ? 'selected' : '' }}>
                        {{ $account->name }} ({{ $account->type_display_name }}) - Rp {{ number_format($account->balance) }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Transfer Account (only for transfer type) -->
        <div id="transfer-account-section" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hidden">
            <label class="block text-sm font-medium text-gray-700 mb-2">Account Tujuan</label>
            <select name="transfer_account_id"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                <option value="">Pilih Account Tujuan</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">
                        {{ $account->name }} ({{ $account->type_display_name }}) - Rp {{ number_format($account->balance) }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- 3. Category Selection -->
        <div id="category-section" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
            
            <!-- Category Selection -->
            <div class="space-y-3">
                <!-- Existing Categories Dropdown -->
                <select name="category_id" id="category-select" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">Pilih kategori...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" data-type="{{ $category->type }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                    <option value="new" class="text-orange-600 font-medium">+ Buat Kategori Baru</option>
                </select>
                
                <!-- New Category Form (Hidden by default) -->
                <div id="new-category-form" class="hidden space-y-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <h3 class="font-medium text-gray-900">Buat Kategori Baru</h3>
                    </div>
                    
                    <!-- Category Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori</label>
                        <input type="text" name="new_category_name" id="new-category-name"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                               placeholder="Contoh: Makan Siang, Transportasi">
                    </div>
                    
                    <!-- Category Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jenis</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="relative">
                                <input type="radio" name="new_category_type" value="expense" checked
                                       class="sr-only peer">
                                <div class="p-2 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-red-500 peer-checked:bg-red-50 text-center">
                                    <span class="text-sm font-medium">{{ __('transactions.expense') }}</span>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="new_category_type" value="income"
                                       class="sr-only peer">
                                <div class="p-2 border border-gray-300 rounded-lg cursor-pointer peer-checked:border-green-500 peer-checked:bg-green-50 text-center">
                                    <span class="text-sm font-medium">{{ __('transactions.income') }}</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex gap-2">
                        <button type="button" id="save-new-category" 
                                class="flex-1 bg-orange-500 hover:bg-orange-600 text-white py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                            Simpan & Gunakan
                        </button>
                        <button type="button" id="cancel-new-category" 
                                class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Quick category buttons -->
            <div class="mt-3">
                <p class="text-xs text-gray-500 mb-2">Kategori Populer:</p>
                <div class="flex flex-wrap gap-2" id="quick-categories">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- 4. Amount Input -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                <input type="number" name="amount" step="0.01" min="0" required
                       class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-lg font-medium"
                       placeholder="0">
            </div>
        </div>

        <!-- 5. Description -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
            <input type="text" name="description" required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                   placeholder="Contoh: Makan siang dengan teman">
        </div>

        <!-- 6. Notes & Picture (Side by Side) -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                    <textarea name="notes" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                              placeholder="Tambahkan catatan tambahan..."></textarea>
                </div>
                
                <!-- Picture Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto Bukti (Opsional)</label>
                    <div class="space-y-3">
                        <!-- File Input -->
                        <input type="file" name="picture" id="picture-input" accept="image/*" 
                               class="hidden" onchange="previewImage(this)">
                        
                        <!-- Upload Button -->
                        <button type="button" onclick="document.getElementById('picture-input').click()"
                                class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-orange-400 hover:bg-orange-50 transition-colors text-center">
                            <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p class="text-sm text-gray-600">Klik untuk upload foto</p>
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, atau GIF (Max 5MB)</p>
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
                            <p class="text-xs text-gray-500 mt-1">Foto akan disimpan sebagai bukti transaksi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button - Moved to bottom -->
        <div class="pt-4">
            <button type="submit" 
                    class="w-full bg-orange-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-orange-600 transition-colors">
                {{ __('transactions.save_transaction') }}
            </button>
        </div>
    </form>
    </div>
</div>

<script>
// Categories data for quick selection
const categories = @json($categories);
const currentTab = 'expense'; // Default tab

// Flash notification function
function showFlashNotification(message, type = 'success') {
    // Remove any existing flash notifications
    const existingNotifications = document.querySelectorAll('.flash-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'flash-notification fixed top-20 left-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg';
    
    // Set colors based on type
    if (type === 'success') {
        notification.className += ' bg-green-100 border border-green-400 text-green-700';
    } else if (type === 'error') {
        notification.className += ' bg-red-100 border border-red-400 text-red-700';
    } else {
        notification.className += ' bg-blue-100 border border-blue-400 text-blue-700';
    }
    
    // Create notification content
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${type === 'success' ? 
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' :
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                    }
                </svg>
                <span>${message}</span>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="hover:opacity-75">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function switchTab(type) {
    const incomeTab = document.getElementById('income-tab');
    const expenseTab = document.getElementById('expense-tab');
    const transferTab = document.getElementById('transfer-tab');
    const categorySection = document.getElementById('category-section');
    const transferAccountSection = document.getElementById('transfer-account-section');
    const transactionTypeInput = document.getElementById('transaction-type');
    
    // Reset all tabs
    [incomeTab, expenseTab, transferTab].forEach(tab => {
        tab.className = 'flex-1 py-3 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 rounded-lg transition-colors';
    });
    
    // Set active tab and update transaction type
    if (type === 'income') {
        incomeTab.className = 'flex-1 py-3 px-4 text-sm font-medium text-orange-500 bg-orange-50 rounded-lg transition-colors';
        categorySection.classList.remove('hidden');
        transferAccountSection.classList.add('hidden');
        transactionTypeInput.value = 'income';
        updateQuickCategories('income');
        filterCategoriesByType('income');
    } else if (type === 'expense') {
        expenseTab.className = 'flex-1 py-3 px-4 text-sm font-medium text-orange-500 bg-orange-50 rounded-lg transition-colors';
        categorySection.classList.remove('hidden');
        transferAccountSection.classList.add('hidden');
        transactionTypeInput.value = 'expense';
        updateQuickCategories('expense');
        filterCategoriesByType('expense');
    } else if (type === 'transfer') {
        transferTab.className = 'flex-1 py-3 px-4 text-sm font-medium text-orange-500 bg-orange-50 rounded-lg transition-colors';
        categorySection.classList.add('hidden');
        transferAccountSection.classList.remove('hidden');
        transactionTypeInput.value = 'transfer';
    }
    
    // Reset form fields
    document.getElementById('category-select').value = '';
    document.getElementById('description').value = '';
    document.getElementById('amount').value = '';
    
    // Hide new category form
    document.getElementById('new-category-form').classList.add('hidden');
}

function updateQuickCategories(type) {
    const quickCategoriesDiv = document.getElementById('quick-categories');
    const filteredCategories = categories.filter(cat => cat.type === type);
    
    quickCategoriesDiv.innerHTML = filteredCategories.slice(0, 6).map(category => `
        <button type="button" 
                class="quick-category-btn px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-orange-100 hover:text-orange-700 transition-colors"
                data-category-id="${category.id}" 
                data-category-name="${category.name}">
            ${category.name}
        </button>
    `).join('');
    
    // Add event listeners to quick category buttons
    document.querySelectorAll('.quick-category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            const categoryName = this.dataset.categoryName;
            
            // Update the select dropdown
            document.getElementById('category-select').value = categoryId;
            
            // Highlight selected button
            document.querySelectorAll('.quick-category-btn').forEach(b => {
                b.classList.remove('bg-orange-100', 'text-orange-700');
                b.classList.add('bg-gray-100', 'text-gray-700');
            });
            this.classList.remove('bg-gray-100', 'text-gray-700');
            this.classList.add('bg-orange-100', 'text-orange-700');
        });
    });
}

function filterCategoriesByType(type) {
    const categorySelect = document.getElementById('category-select');
    const allOptions = categorySelect.querySelectorAll('option');
    
    // Show/hide options based on type
    allOptions.forEach(option => {
        if (option.value === '' || option.value === 'new') {
            // Always show placeholder and "create new" options
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
    
    // Reset selection if current selection doesn't match the type
    const currentValue = categorySelect.value;
    if (currentValue && currentValue !== 'new') {
        const selectedOption = categorySelect.querySelector(`option[value="${currentValue}"]`);
        if (selectedOption && selectedOption.getAttribute('data-type') !== type) {
            categorySelect.value = '';
        }
    }
    
    // Debug log
    console.log('Filtering categories for type:', type);
    console.log('Available options after filtering:', Array.from(allOptions).filter(opt => opt.style.display !== 'none').map(opt => ({value: opt.value, text: opt.textContent, type: opt.getAttribute('data-type')})));
}

// Image preview functionality
function previewImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            showFlashNotification('Ukuran file terlalu besar. Maksimal 5MB.', 'error');
            input.value = '';
            return;
        }
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            showFlashNotification('File harus berupa gambar.', 'error');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    document.getElementById('picture-input').value = '';
    document.getElementById('image-preview').classList.add('hidden');
    document.getElementById('preview-img').src = '';
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing transaction form...');
    console.log('Categories data:', categories);
    
    updateQuickCategories('expense'); // Start with expense categories
    filterCategoriesByType('expense'); // Filter categories to show only expense categories
    populateDateTime(); // Populate date and time with user's local time
    
    // Category selection handlers
    setupCategoryHandlers();
    
    console.log('Transaction form initialized');
});

// Function to populate date and time with user's local time
function populateDateTime() {
    const now = new Date();
    
    // Format date as YYYY-MM-DD for date input
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const dateString = `${year}-${month}-${day}`;
    
    // Format time as HH:MM for time input
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const timeString = `${hours}:${minutes}`;
    
    // Set the values
    document.getElementById('date-input').value = dateString;
    document.getElementById('time-input').value = timeString;
    
    console.log('User local time:', now.toString());
    console.log('Date input value:', dateString);
    console.log('Time input value:', timeString);
}

// Function to refresh date and time to current moment
function refreshDateTime() {
    const now = new Date();
    const dateInput = document.getElementById('date-input');
    const timeInput = document.getElementById('time-input');

    // Format date as YYYY-MM-DD for date input
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const dateString = `${year}-${month}-${day}`;
    
    // Format time as HH:MM for time input
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const timeString = `${hours}:${minutes}`;
    
    // Set the values
    dateInput.value = dateString;
    timeInput.value = timeString;

    console.log('Refreshed date and time to current moment:', now.toString());
    console.log('Date input value:', dateString);
    console.log('Time input value:', timeString);
}

// Form submission
document.getElementById('transaction-form').addEventListener('submit', function(e) {
    // Validate required fields
    const amount = document.querySelector('input[name="amount"]').value;
    const accountId = document.querySelector('select[name="account_id"]').value;
    const description = document.querySelector('input[name="description"]').value;
    
    if (!amount || !accountId || !description) {
        e.preventDefault();
        showFlashNotification('Mohon lengkapi semua field yang wajib diisi', 'error');
        return;
    }
    
    // For transfer type, validate transfer account
    const currentTab = document.querySelector('.bg-orange-50').textContent.trim();
    if (currentTab === 'Transfer') {
        const transferAccountId = document.querySelector('select[name="transfer_account_id"]').value;
        if (!transferAccountId) {
            e.preventDefault();
            showFlashNotification('Mohon pilih account tujuan untuk transfer', 'error');
            return;
        }
    }
    
    // If validation passes, form will submit normally
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';
});

// Category handlers
function setupCategoryHandlers() {
    const categorySelect = document.getElementById('category-select');
    const newCategoryForm = document.getElementById('new-category-form');
    const saveNewCategoryBtn = document.getElementById('save-new-category');
    const cancelNewCategoryBtn = document.getElementById('cancel-new-category');
    
    // Handle category selection
    categorySelect.addEventListener('change', function() {
        if (this.value === 'new') {
            newCategoryForm.classList.remove('hidden');
        } else {
            newCategoryForm.classList.add('hidden');
        }
    });
    
    // Handle save new category
    saveNewCategoryBtn.addEventListener('click', async function() {
        const categoryName = document.getElementById('new-category-name').value.trim();
        const categoryType = document.querySelector('input[name="new_category_type"]:checked').value;
        
        if (!categoryName) {
            showFlashNotification('Nama kategori harus diisi!', 'error');
            return;
        }
        
        try {
            // Create new category via AJAX using FormData
            const formData = new FormData();
            formData.append('name', categoryName);
            formData.append('type', categoryType);
            formData.append('icon', 'dots-horizontal');
            formData.append('color', '#6B7280');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            const response = await fetch('/categories', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            if (response.ok) {
                const result = await response.json();
                
                // Add new category to select dropdown
                const newOption = document.createElement('option');
                newOption.value = result.category.id;
                newOption.textContent = result.category.name;
                newOption.setAttribute('data-type', categoryType);
                
                // Insert before "Buat Kategori Baru" option
                const newCategoryOption = categorySelect.querySelector('option[value="new"]');
                categorySelect.insertBefore(newOption, newCategoryOption);
                
                // Select the new category
                categorySelect.value = result.category.id;
                
                // Hide form and reset
                newCategoryForm.classList.add('hidden');
                document.getElementById('new-category-name').value = '';
                
                // Update quick categories
                updateQuickCategories(categoryType);
                
                // Filter categories to show only the current type
                filterCategoriesByType(categoryType);
                
                // Show success message
                showFlashNotification('Kategori berhasil dibuat!', 'success');
            } else {
                // Try to parse JSON error response
                let errorMessage = 'Gagal membuat kategori';
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorData.errors ? Object.values(errorData.errors).flat().join(', ') : errorMessage;
                } catch (e) {
                    // If response is not JSON, show generic error
                    errorMessage = `Error ${response.status}: ${response.statusText}`;
                }
                showFlashNotification('Error: ' + errorMessage, 'error');
            }
        } catch (error) {
            console.error('Error creating category:', error);
            showFlashNotification('Terjadi kesalahan saat membuat kategori', 'error');
        }
    });
    
    // Handle cancel new category
    cancelNewCategoryBtn.addEventListener('click', function() {
        newCategoryForm.classList.add('hidden');
        document.getElementById('new-category-name').value = '';
        categorySelect.value = '';
    });
}
</script>
@endsection
