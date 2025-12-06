@props(['title'])

<div class="flex items-center justify-between p-4 border-b border-gray-100">
    <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
    @if(isset($action))
        <div class="action">
            {{ $action }}
        </div>
    @endif
</div>
