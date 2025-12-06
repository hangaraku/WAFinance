@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Tambah Budget Baru</h1>
                    <p class="text-sm text-gray-600">Buat anggaran untuk kategori pengeluaran</p>
                </div>
                <a href="{{ route('budgets.index') }}" 
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
        <form action="{{ route('budgets.store') }}" method="POST" class="max-w-2xl mx-auto">
            @csrf
            
            <!-- Category Selection -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Kategori Pengeluaran *</label>
                <select name="category_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">Pilih kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Amount -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Jumlah Budget *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                    <input type="number" name="amount" required min="0" step="1000"
                           value="{{ old('amount') }}"
                           placeholder="0"
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                </div>
                @error('amount')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Month and Year -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Periode Budget *</label>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-600 mb-2">Bulan</label>
                        <select name="month" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="">Pilih bulan</option>
                            <option value="1" {{ old('month') == 1 ? 'selected' : '' }}>Januari</option>
                            <option value="2" {{ old('month') == 2 ? 'selected' : '' }}>Februari</option>
                            <option value="3" {{ old('month') == 3 ? 'selected' : '' }}>Maret</option>
                            <option value="4" {{ old('month') == 4 ? 'selected' : '' }}>April</option>
                            <option value="5" {{ old('month') == 5 ? 'selected' : '' }}>Mei</option>
                            <option value="6" {{ old('month') == 6 ? 'selected' : '' }}>Juni</option>
                            <option value="7" {{ old('month') == 7 ? 'selected' : '' }}>Juli</option>
                            <option value="8" {{ old('month') == 8 ? 'selected' : '' }}>Agustus</option>
                            <option value="9" {{ old('month') == 9 ? 'selected' : '' }}>September</option>
                            <option value="10" {{ old('month') == 10 ? 'selected' : '' }}>Oktober</option>
                            <option value="11" {{ old('month') == 11 ? 'selected' : '' }}>November</option>
                            <option value="12" {{ old('month') == 12 ? 'selected' : '' }}>Desember</option>
                        </select>
                        @error('month')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-2">Tahun</label>
                        <select name="year" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="">Pilih tahun</option>
                            @for($year = date('Y') - 2; $year <= date('Y') + 3; $year++)
                                <option value="{{ $year }}" {{ old('year') == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endfor
                        </select>
                        @error('year')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Catatan (Opsional)</label>
                <textarea name="notes" rows="3" 
                          placeholder="Tambahkan catatan untuk budget ini..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex space-x-4">
                <a href="{{ route('budgets.index') }}" 
                   class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-200 transition-colors text-center">
                    Batal
                </a>
                <button type="submit" 
                        class="flex-1 bg-orange-500 text-white px-6 py-3 rounded-lg hover:bg-orange-600 transition-colors">
                    Simpan Budget
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-select current month and year
document.addEventListener('DOMContentLoaded', function() {
    const currentMonth = new Date().getMonth() + 1;
    const currentYear = new Date().getFullYear();
    
    document.querySelector('select[name="month"]').value = currentMonth;
    document.querySelector('select[name="year"]').value = currentYear;
});
</script>
@endsection
