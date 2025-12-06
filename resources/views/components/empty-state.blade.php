@props(['icon', 'title', 'description'])

<div class="text-center py-8">
    @if(isset($icon))
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            {{ $icon }}
        </div>
    @endif
    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ $title }}</h4>
    <p class="text-gray-500 mb-4">{{ $description }}</p>
    @if(isset($action))
        <div class="action">
            {{ $action }}
        </div>
    @endif
</div>
