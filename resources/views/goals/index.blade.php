@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">{{ __('common.financial_goals') }}</h1>
                    <p class="text-sm text-gray-600">{{ __('common.manage_targets') }}</p>
                </div>
                <a href="{{ route('goals.create') }}" 
                   class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    {{ __('common.add_goal') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Total Savings Summary -->
    <div class="px-4 py-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900">Total Tabungan</h2>
                <div class="text-4xl font-bold text-green-600 mt-2">
                    Rp {{ number_format($totalSavings) }}
                </div>
                <p class="text-sm text-gray-600 mt-2">Dari semua account aktif</p>
            </div>
        </div>

        <!-- Goals List -->
        <div class="space-y-4">
            @forelse($goals as $goal)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <h3 class="text-xl font-semibold text-gray-900">{{ $goal->name }}</h3>
                            @if($goal->is_completed)
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Selesai</span>
                            @endif
                            <span class="bg-{{ $goal->priority === 'high' ? 'red' : ($goal->priority === 'medium' ? 'yellow' : 'green') }}-100 text-{{ $goal->priority === 'high' ? 'red' : ($goal->priority === 'medium' ? 'yellow' : 'green') }}-800 text-xs px-2 py-1 rounded-full">
                                {{ ucfirst($goal->priority) }}
                            </span>
                        </div>
                        
                        @if($goal->description)
                            <p class="text-gray-600 mb-3">{{ $goal->description }}</p>
                        @endif
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Target</p>
                                <p class="text-lg font-bold text-gray-900">Rp {{ number_format($goal->target_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Terkumpul</p>
                                <p class="text-lg font-bold text-green-600">Rp {{ number_format($goal->current_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Sisa</p>
                                <p class="text-lg font-bold text-blue-600">Rp {{ number_format($goal->remaining_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Target Date</p>
                                <p class="text-lg font-bold text-gray-900">{{ \Carbon\Carbon::parse($goal->target_date)->format('d M Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Progress</span>
                        <span>{{ round($goal->progress_percentage, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-green-500 h-3 rounded-full transition-all duration-300" 
                             style="width: {{ $goal->progress_percentage }}%"></div>
                    </div>
                </div>
                
                <!-- Goal Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="flex space-x-2">
                        <a href="{{ route('goals.edit', $goal) }}" 
                           class="text-sm text-gray-600 hover:text-gray-800">Edit</a>
                    </div>
                    
                    <div class="flex space-x-2">
                        @if(!$goal->is_completed)
                            <form action="{{ route('goals.toggle-complete', $goal) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-green-600 hover:text-green-800">
                                    Tandai Selesai
                                </button>
                            </form>
                        @else
                            <form action="{{ route('goals.toggle-complete', $goal) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-blue-600 hover:text-blue-800">
                                    Buka Kembali
                                </button>
                            </form>
                        @endif
                        
                        <form action="{{ route('goals.destroy', $goal) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800" 
                                    onclick="return confirm('Yakin ingin menghapus tujuan ini?')">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada tujuan keuangan</h3>
                <p class="text-gray-600 mb-4">Mulai dengan membuat tujuan keuangan pertama Anda.</p>
                <a href="{{ route('goals.create') }}" 
                   class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition-colors">
                    Buat Tujuan Pertama
                </a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
