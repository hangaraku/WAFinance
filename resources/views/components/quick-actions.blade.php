@props(['title' => 'Aksi Cepat'])

<div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $title }}</h3>
    <div class="grid grid-cols-2 gap-3">
        {{ $slot }}
    </div>
</div>
