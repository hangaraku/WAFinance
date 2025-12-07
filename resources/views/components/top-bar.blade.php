@props([
    'title' => '',
    'showLogo' => true,
    'showSearch' => true,
    'showNotifications' => true,
    'showUserAvatar' => false,
    'userName' => '',
    'notifications' => []
])

<div class="bg-white h-12 flex items-center justify-between px-4 border-b border-gray-200">
    <!-- Left side - Logo and Title -->
    <div class="flex items-center space-x-3">
        @if($showLogo)
            <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                <span class="text-white font-bold text-sm">CF</span>
            </div>
        @endif
        @if($title)
            <h1 class="text-lg font-bold text-gray-900">{{ $title }}</h1>
        @endif
    </div>
    
    <!-- Right side - Actions -->
    <div class="flex items-center space-x-3">
        @if($showSearch)
            <button id="search-toggle" class="p-2 text-gray-600 hover:text-orange-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
        @endif
        
        @if($showNotifications)
            <div class="relative">
                <button class="p-2 text-gray-600 hover:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                    </svg>
                </button>
                @if(count($notifications) > 0)
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ count($notifications) }}</span>
                @endif
            </div>
        @endif
        
        @if($showUserAvatar && $userName)
            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                <span class="text-gray-600 font-medium text-sm">{{ substr($userName, 0, 1) }}</span>
            </div>
        @endif
        
        @if(isset($actions))
            <div class="actions">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
