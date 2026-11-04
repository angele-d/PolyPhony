<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mes Playlists</h2>
            <a href="{{ route('playlists.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                + Nouvelle Playlist
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @forelse ($playlists as $playlist)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-bold mb-2">{{ $playlist->playlist_name }}</h3>
                        <p class="text-gray-600 text-sm mb-4">{{ Str::limit($playlist->description, 100) }}</p>
                        <div class="text-xs text-gray-400 mb-4">
                            Créée par : {{ $playlist->administrator->name }}
                        </div>
                        <a href="{{ route('playlists.show', $playlist) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm">Voir la playlist &rarr;</a>
                    </div>
                @empty
                    <div class="col-span-3 bg-white p-6 text-center text-gray-500 rounded-lg">
                        Aucune playlist pour le moment.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>