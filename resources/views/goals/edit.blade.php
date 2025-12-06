@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Edit Tujuan Keuangan</h1>
                    <p class="text-sm text-gray-600">Update target dan impian keuangan Anda</p>
                </div>
                <a href="{{ route('goals.index') }}" 
                   class="text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="px-4 py-6">
        <form action="{{ route('goals.update', $goal) }}" method="POST" class="max-w-2xl mx-auto">
            @csrf
            @method('PUT')
            
            <!-- Goal Name -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Nama Tujuan *</label>
                <input type="text" name="name" required maxlength="255"
                       value="{{ old('name', $goal->name) }}"
                       placeholder="Contoh: Liburan ke Bali, DP Rumah, dll"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                @error('name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Deskripsi (Opsional)</label>
                <textarea name="description" rows="3" maxlength="1000"
                          placeholder="Jelaskan detail tujuan keuangan Anda..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">{{ old('description', $goal->description) }}</textarea>
                @error('description')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Target Amount -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Target Jumlah *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                    <input type="number" name="target_amount" required min="0" step="1000"
                           value="{{ old('target_amount', $goal->target_amount) }}"
                           placeholder="0"
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                </div>
                @error('target_amount')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Current Amount -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Jumlah Terkumpul Saat Ini</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                    <input type="number" name="current_amount" min="0" step="1000"
                           value="{{ old('current_amount', $goal->current_amount) }}"
                           placeholder="0"
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                </div>
                <p class="text-xs text-gray-500 mt-2">Update jumlah tabungan yang sudah terkumpul</p>
                @error('current_amount')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Target Date -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Target Tanggal *</label>
                <input type="date" name="target_date" required
                       value="{{ old('target_date', $goal->target_date) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-2">Update tanggal target yang ingin dicapai</p>
                @error('target_date')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Priority -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Prioritas *</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-orange-300">
                        <input type="radio" name="priority" value="low" {{ old('priority', $goal->priority) == 'low' ? 'checked' : '' }} class="mr-2">
                        <span class="text-sm">Rendah</span>
                    </label>
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-orange-300">
                        <input type="radio" name="priority" value="medium" {{ old('priority', $goal->priority) == 'medium' ? 'checked' : '' }} class="mr-2">
                        <span class="text-sm">Sedang</span>
                    </label>
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-orange-300">
                        <input type="radio" name="priority" value="high" {{ old('priority', $goal->priority) == 'high' ? 'checked' : '' }} class="mr-2">
                        <span class="text-sm">Tinggi</span>
                    </label>
                </div>
                @error('priority')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Kategori (Opsional)</label>
                <input type="text" name="category" maxlength="100"
                       value="{{ old('category', $goal->category) }}"
                       placeholder="Contoh: Liburan, Investasi, Properti, dll"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                @error('category')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Catatan (Opsional)</label>
                <textarea name="notes" rows="3" maxlength="1000"
                          placeholder="Tambahkan catatan atau rencana untuk mencapai tujuan ini..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">{{ old('notes', $goal->notes) }}</textarea>
                @error('notes')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex space-x-4">
                <a href="{{ route('goals.index') }}" 
                   class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-200 transition-colors text-center">
                    Batal
                </a>
                <button type="submit" 
                        class="flex-1 bg-orange-500 text-white px-6 py-3 rounded-lg hover:bg-orange-600 transition-colors">
                    Update Tujuan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
