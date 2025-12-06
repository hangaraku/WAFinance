@props(['title', 'showBack' => false, 'backUrl' => '#'])

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center space-x-3">
        @if($showBack)
            <a href="{{ $backUrl }}" class="p-2 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
        @endif
        <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
    </div>
    
    @if(isset($actions))
        <div class="actions">
            {{ $actions }}
        </div>
    @endif
</div>
