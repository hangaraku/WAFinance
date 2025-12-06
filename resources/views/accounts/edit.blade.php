@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 py-4">
            <div class="flex items-center space-x-4">
                <a href="{{ route('accounts.index') }}" class="text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Edit Account</h1>
                    <p class="text-sm text-gray-600">Update account information</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="px-4 py-6">
        <div class="max-w-2xl mx-auto">
            <form action="{{ route('accounts.update', $account) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <!-- Account Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $account->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                           placeholder="e.g., Bank BCA, Cash, Credit Card BCA" required>
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Account Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                    <select name="type" id="type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        <option value="">Select account type</option>
                        <option value="cash" {{ $account->type === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank" {{ $account->type === 'bank' ? 'selected' : '' }}>Bank Account</option>
                        <option value="credit_card" {{ $account->type === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                        <option value="investment" {{ $account->type === 'investment' ? 'selected' : '' }}>Investment</option>
                        <option value="wallet" {{ $account->type === 'wallet' ? 'selected' : '' }}>Digital Wallet</option>
                    </select>
                    @error('type')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Current Balance -->
                <div>
                    <label for="balance" class="block text-sm font-medium text-gray-700 mb-2">Current Balance</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                        <input type="number" name="balance" id="balance" step="0.01" value="{{ old('balance', $account->balance) }}"
                               class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                               placeholder="0.00" required>
                    </div>
                    @error('balance')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Color -->
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-2">Color Theme</label>
                    <div class="grid grid-cols-6 gap-2">
                        @foreach(['blue', 'green', 'purple', 'orange', 'red', 'yellow', 'pink', 'indigo', 'teal', 'gray'] as $color)
                        <label class="flex items-center justify-center">
                            <input type="radio" name="color" value="{{ $color }}" 
                                   class="sr-only peer" {{ $account->color === $color ? 'checked' : '' }}>
                            <div class="w-8 h-8 rounded-full bg-{{ $color }}-500 border-2 border-transparent peer-checked:border-gray-900 cursor-pointer"></div>
                        </label>
                        @endforeach
                    </div>
                    @error('color')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Icon -->
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 mb-2">Icon</label>
                    <div class="grid grid-cols-4 gap-3">
                        @php
                            $icons = [
                                'banknotes' => ['name' => 'Cash', 'path' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
                                'building-library' => ['name' => 'Bank', 'path' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'],
                                'credit-card' => ['name' => 'Credit Card', 'path' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                                'chart-bar' => ['name' => 'Investment', 'path' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                                'wallet' => ['name' => 'Wallet', 'path' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                                'home' => ['name' => 'Home', 'path' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                                'car' => ['name' => 'Car', 'path' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                                'gift' => ['name' => 'Gift', 'path' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7'],
                                'star' => ['name' => 'Star', 'path' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                                'heart' => ['name' => 'Heart', 'path' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                                'truck' => ['name' => 'Truck', 'path' => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'],
                                'plane' => ['name' => 'Plane', 'path' => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8']
                            ];
                        @endphp
                        
                        @foreach($icons as $iconKey => $iconData)
                        <label class="flex flex-col items-center p-3 border-2 {{ $account->icon === $iconKey ? 'border-orange-500 bg-orange-50' : 'border-gray-200' }} rounded-lg cursor-pointer hover:border-orange-300 transition-colors">
                            <input type="radio" name="icon" value="{{ $iconKey }}" 
                                   class="sr-only peer" {{ $account->icon === $iconKey ? 'checked' : '' }}>
                            <div class="w-8 h-8 {{ $account->icon === $iconKey ? 'text-orange-600' : 'text-gray-600' }} peer-checked:text-orange-600 mb-2">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconData['path'] }}"></path>
                                </svg>
                            </div>
                            <span class="text-xs text-gray-600 text-center">{{ $iconData['name'] }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('icon')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                              placeholder="Brief description of this account">{{ old('description', $account->description) }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Account Status Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Account Status</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Default Account:</span>
                            <span class="font-medium">{{ $account->is_default ? 'Yes' : 'No' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Active:</span>
                            <span class="font-medium">{{ $account->is_active ? 'Yes' : 'No' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Transactions:</span>
                            <span class="font-medium">{{ $account->transactions()->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Created:</span>
                            <span class="font-medium">{{ $account->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex space-x-4 pt-4">
                    <a href="{{ route('accounts.index') }}" 
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-center">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="flex-1 bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors">
                        Update Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-select icon based on account type
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const iconMap = {
        'cash': 'banknotes',
        'bank': 'building-library',
        'credit_card': 'credit-card',
        'investment': 'chart-bar',
        'wallet': 'wallet'
    };
    
    if (iconMap[type]) {
        const iconRadio = document.querySelector(`input[name="icon"][value="${iconMap[type]}"]`);
        if (iconRadio) {
            iconRadio.checked = true;
            // Update visual selection
            updateIconSelection(iconMap[type]);
        }
    }
});

// Auto-select color based on account type
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const colorMap = {
        'cash': 'green',
        'bank': 'blue',
        'credit_card': 'purple',
        'investment': 'yellow',
        'wallet': 'orange'
    };
    
    if (colorMap[type]) {
        const colorRadio = document.querySelector(`input[name="color"][value="${colorMap[type]}"]`);
        if (colorRadio) {
            colorRadio.checked = true;
        }
    }
});

// Update icon selection visual
function updateIconSelection(selectedIcon) {
    // Remove all selected states
    document.querySelectorAll('input[name="icon"]').forEach(radio => {
        const label = radio.closest('label');
        label.classList.remove('border-orange-500', 'bg-orange-50');
        label.classList.add('border-gray-200');
        
        const iconDiv = label.querySelector('div');
        iconDiv.classList.remove('text-orange-600');
        iconDiv.classList.add('text-gray-600');
    });
    
    // Add selected state to chosen icon
    const selectedRadio = document.querySelector(`input[name="icon"][value="${selectedIcon}"]`);
    if (selectedRadio) {
        const label = selectedRadio.closest('label');
        label.classList.remove('border-gray-200');
        label.classList.add('border-orange-500', 'bg-orange-50');
        
        const iconDiv = label.querySelector('div');
        iconDiv.classList.remove('text-gray-600');
        iconDiv.classList.add('text-orange-600');
    }
}

// Add click handlers for icon selection
document.querySelectorAll('input[name="icon"]').forEach(radio => {
    radio.addEventListener('change', function() {
        updateIconSelection(this.value);
    });
});
</script>
@endsection
