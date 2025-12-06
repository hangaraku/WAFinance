@props(['activeTab' => 'harian'])

<div class="bg-white rounded-xl p-1 shadow-sm border border-gray-100 mb-6">
    <div class="flex justify-center space-x-1 overflow-x-auto scrollbar-hide">
        <button class="flex-shrink-0 py-2 px-4 text-sm font-medium {{ $activeTab === 'harian' ? 'text-orange-500 bg-orange-50' : 'text-gray-500 hover:text-gray-700' }} rounded-lg whitespace-nowrap">
            Harian
        </button>
        <button class="flex-shrink-0 py-2 px-4 text-sm font-medium {{ $activeTab === 'kalender' ? 'text-orange-500 bg-orange-50' : 'text-gray-500 hover:text-gray-700' }} rounded-lg whitespace-nowrap">
            Kalender
        </button>
        <button class="flex-shrink-0 py-2 px-4 text-sm font-medium {{ $activeTab === 'bulanan' ? 'text-orange-500 bg-orange-50' : 'text-gray-500 hover:text-gray-700' }} rounded-lg whitespace-nowrap">
            Bulanan
        </button>
    </div>
</div>
