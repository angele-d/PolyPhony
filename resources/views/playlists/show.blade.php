<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    {{ $playlist->playlist_name }}
                </h2>
                <p class="text-sm text-gray-500">
                    Administrateur : <span class="font-medium text-gray-700">{{ $playlist->administrator->name }}</span>
                </p>
            </div>
            <a href="{{ route('playlists.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm font-semibold">
                &larr; Retour aux playlists
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Description --}}
            @if ($playlist->description)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-bold mb-2 text-gray-800">Description</h3>
                    <p class="text-gray-600">{{ $playlist->description }}</p>
                </div>
            @endif

            {{-- Grille des contenus --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Section Chansons (Songs) --}}
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Chansons ({{ $playlist->songs->count() }})</h3>
                    </div>
                    @forelse ($playlist->songs as $song)
                        <div class="border-b py-2 flex justify-between items-center">
                            <div>
                                <div class="font-semibold text-gray-800">{{ $song->song_name }}</div>
                                <div class="text-xs text-gray-500">{{ $song->artist_name }}</div>
                            </div>
                            <a href="{{ $song->source_url }}" target="_blank" class="text-xs text-blue-600 hover:underline">Songfacts &rarr;</a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Aucune chanson ajoutée pour le moment.</p>
                    @endforelse
                </div>

                {{-- Section Contributeurs --}}
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Contributeurs ({{ $playlist->contributors->count() }})</h3>
                    </div>
                    @forelse ($playlist->contributors as $contributor)
                        <div class="border-b py-2 flex justify-between items-center">
                            <span class="text-gray-700 text-sm font-medium">{{ $contributor->name }}</span>
                            <span class="text-xs text-gray-400">{{ $contributor->email }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Aucun contributeur dans cette playlist.</p>
                    @endforelse
                </div>

                {{-- Section Chanteurs / Artistes --}}
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Artistes / Chanteurs ({{ $playlist->singers->count() }})</h3>
                    @forelse ($playlist->singers as $singer)
                        <div class="border-b py-2">
                            <div class="font-semibold text-gray-800">{{ $singer->singer_name }}</div>
                            <div class="text-xs text-gray-500">{{ Str::limit($singer->bio_summary, 80) }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Aucune fiche artiste créée.</p>
                    @endforelse
                </div>

                {{-- Section Documents Chiffrés --}}
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Documents Chiffrés ({{ $playlist->documents->count() }})</h3>
                    @forelse ($playlist->documents as $doc)
                        <div class="border-b py-2 flex justify-between items-center">
                            <span class="text-sm text-gray-700 font-medium">{{ $doc->original_name }}</span>
                            <span class="text-xs text-gray-400">{{ $doc->mime_type }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Aucun document téléversé.</p>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</x-app-layout>