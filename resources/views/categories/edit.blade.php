@extends('layouts.app')

@section('title', 'Edit Kategori')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Edit Kategori</h1>
                    <p class="text-sm text-gray-600">Ubah detail kategori</p>
                </div>
                <a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="px-4 py-6">
        <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Category Name -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Kategori</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name', $category->name) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('name') border-red-500 @enderror"
                       placeholder="Contoh: Makan Siang, Transportasi, dll"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category Type -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <label class="block text-sm font-medium text-gray-700 mb-3">Jenis Kategori</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative">
                        <input type="radio" 
                               name="type" 
                               value="expense" 
                               {{ old('type', $category->type) === 'expense' ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-red-500 peer-checked:bg-red-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m16 0l-4-4m4 4l-4 4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">Pengeluaran</h3>
                                    <p class="text-sm text-gray-500">Untuk transaksi keluar</p>
                                </div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="relative">
                        <input type="radio" 
                               name="type" 
                               value="income" 
                               {{ old('type', $category->type) === 'income' ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-green-500 peer-checked:bg-green-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">Pemasukan</h3>
                                    <p class="text-sm text-gray-500">Untuk transaksi masuk</p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
                @error('type')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Icon Selection -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <label class="block text-sm font-medium text-gray-700 mb-3">Pilih Icon</label>
                <div class="grid grid-cols-4 gap-3" id="icon-grid">
                    @php
                        $icons = [
                            'cake' => 'M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A2.704 2.704 0 003 15.546V12a9 9 0 1118 0v3.546z',
                            'truck' => 'M8 17a2 2 0 100 4 2 2 0 000-4zM8 17V9m0 8l-2-2m2 2l2-2M8 9V7a2 2 0 012-2h4a2 2 0 012 2v2m-6 0h6m-6 0l-2 2m8-2l2 2',
                            'document-text' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                            'shopping-bag' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
                            'currency-dollar' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
                            'gift' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
                            'trending-up' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
                            'home' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                            'heart' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                            'star' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                            'plus-circle' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
                            'dots-horizontal' => 'M5 12h.01M12 12h.01M19 12h.01'
                        ];
                    @endphp
                    
                    @foreach($icons as $iconName => $iconPath)
                        <label class="relative">
                            <input type="radio" 
                                   name="icon" 
                                   value="{{ $iconName }}" 
                                   {{ old('icon', $category->icon) === $iconName ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="p-3 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-orange-500 peer-checked:bg-orange-50 transition-colors">
                                <svg class="w-6 h-6 mx-auto text-gray-600 peer-checked:text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path>
                                </svg>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('icon')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Color Selection -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <label class="block text-sm font-medium text-gray-700 mb-3">Pilih Warna</label>
                <div class="grid grid-cols-6 gap-3" id="color-grid">
                    @php
                        $colors = [
                            '#FF6B35' => 'Orange',
                            '#4F46E5' => 'Indigo', 
                            '#DC2626' => 'Red',
                            '#059669' => 'Green',
                            '#F59E0B' => 'Yellow',
                            '#8B5CF6' => 'Purple',
                            '#EF4444' => 'Red',
                            '#10B981' => 'Emerald',
                            '#3B82F6' => 'Blue',
                            '#F97316' => 'Orange',
                            '#84CC16' => 'Lime',
                            '#EC4899' => 'Pink'
                        ];
                    @endphp
                    
                    @foreach($colors as $colorValue => $colorName)
                        <label class="relative">
                            <input type="radio" 
                                   name="color" 
                                   value="{{ $colorValue }}" 
                                   {{ old('color', $category->color) === $colorValue ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-10 h-10 rounded-lg cursor-pointer border-2 border-gray-200 peer-checked:border-gray-800 transition-colors" 
                                 style="background-color: {{ $colorValue }};">
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('color')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 px-4 rounded-lg font-medium transition-colors duration-200">
                    Perbarui Kategori
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

