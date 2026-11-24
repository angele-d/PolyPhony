<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Create a Playlist') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form action="{{ route('playlists.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2" for="playlist_name">{{ __('Playlist Name') }}</label>
                        <input type="text" name="playlist_name" id="playlist_name" class="w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2" for="description">{{ __('Description') }}</label>
                        <textarea name="description" id="description" rows="4" class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('playlists.index') }}" class="px-4 py-2 border rounded-md text-gray-600">{{ __('Cancel') }}</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-gray-800 font-bold py-2 px-4 rounded-md">{{ __('Create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>