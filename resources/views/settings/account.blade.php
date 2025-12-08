@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <h2 class="text-lg font-semibold mb-4">{{ __('settings.user') }}</h2>

        <p class="text-sm text-gray-600 mb-4">{{ __('settings.user_description') }}</p>

        <form method="POST" action="{{ route('settings.account.update') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">{{ __('settings.name') ?? 'Name' }}</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full border-gray-200 rounded-lg shadow-sm" required />
                @error('name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">{{ __('settings.whatsapp_number') ?? 'WhatsApp Number' }}</label>
                <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" class="mt-1 block w-full border-gray-200 rounded-lg shadow-sm" placeholder="+6281234567890" />
                @error('whatsapp_number')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center space-x-3">
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg">{{ __('settings.connect') ?? 'Hubungkan' }}</button>

                @if($user->whatsapp_number)
                    <form method="POST" action="{{ route('settings.whatsapp.remove') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">{{ __('settings.remove_whatsapp') ?? 'Hapus' }}</button>
                    </form>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
