@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-gray-100 ' . $class]) }}>
    {{ $slot }}
</div>
