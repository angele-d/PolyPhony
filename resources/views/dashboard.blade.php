<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                {{ __('My playlists') }}
            </h2>
            <a href="{{ route('playlists.create') }}" class="bg-blue-600 hover:bg-blue-700 text-gray-800 font-bold py-2 px-4 rounded-md text-sm shadow-sm transition">
                + {{ __('New Playlist') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">

            {{-- Message de confirmation --}}
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Section 1 : Playlists the user has created --}}
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-xl">👑</span>
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ __('Playlists I have created') }} ({{ $createdPlaylists->count() }})
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @forelse ($createdPlaylists as $playlist)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500 flex flex-col justify-between">
                            <div>
                                <h4 class="text-lg font-bold text-gray-900 mb-2">{{ $playlist->playlist_name }}</h4>
                                <p class="text-gray-600 text-sm mb-4">
                                    {{ Str::limit($playlist->description, 100) ?: __('No description available.') }}
                                </p>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 mb-4 border-t pt-3 flex justify-between">
                                    <span>{{ __('Role') }} : {{ __('Administrator') }}</span>
                                    <span>{{ $playlist->added_at ? \Carbon\Carbon::parse($playlist->added_at)->format('d/m/Y') : '' }}</span>
                                </div>
                                <a href="{{ route('playlists.show', $playlist) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm inline-flex items-center gap-1">
                                    {{ __('Enter the playlist') }} &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 bg-white p-6 text-center text-gray-500 rounded-lg shadow-sm border border-gray-100">
                            {{ __('You\'ve never created a playlist yet. Click the "New Playlist" button above to get started!') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Section 2 : Playlists where the user is a contributor --}}
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-xl">🤝</span>
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ __('Playlists where I\'m a contributor') }} ({{ $contributedPlaylists->count() }})
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @forelse ($contributedPlaylists as $playlist)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-purple-500 flex flex-col justify-between">
                            <div>
                                <h4 class="text-lg font-bold text-gray-900 mb-2">{{ $playlist->playlist_name }}</h4>
                                <p class="text-gray-600 text-sm mb-4">
                                    {{ Str::limit($playlist->description, 100) ?: __('No description available.') }}
                                </p>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 mb-4 border-t pt-3">
                                    {{ __('Created by') }} : <span class="font-medium text-gray-700">{{ $playlist->administrator->name }}</span>
                                </div>
                                <a href="{{ route('playlists.show', $playlist) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm inline-flex items-center gap-1">
                                    {{ __('Enter the playlist') }} &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 bg-white p-6 text-center text-gray-500 rounded-lg shadow-sm border border-gray-100">
                            {{ __('You are not invited to contribute to any playlist at the moment.') }}
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>