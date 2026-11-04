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
            <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm font-semibold">
                &larr; Retour au tableau de bord
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Messages d'alerte --}}
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md shadow-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Moteur de Recherche Intelligente Songfacts (Alpine.js) --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg border-2 border-indigo-100" 
                 x-data="{
                    query: '',
                    loading: false,
                    directData: null,
                    proposals: [],
                    search() {
                        if (this.query.length < 2) {
                            this.proposals = [];
                            this.directData = null;
                            return;
                        }
                        this.loading = true;
                        fetch('{{ route('search.songfacts') }}?q=' + encodeURIComponent(this.query))
                            .then(res => res.json())
                            .then(data => {
                                this.loading = false;
                                if (data.direct) {
                                    this.directData = data.data;
                                    this.proposals = [];
                                } else {
                                    this.directData = null;
                                    this.proposals = data.proposals || [];
                                }
                            })
                            .catch(() => { this.loading = false; });
                    }
                 }">
                <h3 class="text-lg font-bold text-gray-800 mb-2">🔍 Recherche & Extraction Songfacts</h3>
                <p class="text-sm text-gray-500 mb-4">Tapez le nom d'un artiste et un morceau (ex: <i>"Coldplay Yellow"</i>) pour importer les détails automatiquement.</p>

                <div class="relative">
                    <input type="text" 
                           x-model="query" 
                           @input.debounce.400ms="search()" 
                           placeholder="Ex: Queen Bohemian Rhapsody..." 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                    <div x-show="loading" class="absolute right-3 top-3 text-xs text-indigo-600 font-semibold">
                        Recherche...
                    </div>
                </div>

                {{-- Résultat Direct Trouvé --}}
                <template x-if="directData">
                    <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
                        <div class="font-bold text-green-900" x-text="directData.song_name + ' — ' + directData.artist_name"></div>
                        <p class="text-xs text-gray-600 mt-1 line-clamp-3" x-text="directData.song_infos"></p>
                        <form action="{{ route('playlists.songs.add-scraped', $playlist) }}" method="POST" class="mt-3">
                            @csrf
                            <input type="hidden" name="source_url" :value="directData.source_url">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-gray-800 font-bold py-1 px-3 rounded text-sm">
                                + Ajouter cette chanson à la playlist
                            </button>
                        </form>
                    </div>
                </template>

                {{-- Propositions d'autocomplétion si non direct --}}
                <template x-if="!directData && proposals.length > 0">
                    <div class="mt-3 bg-white border rounded-md shadow-lg divide-y">
                        <template x-for="item in proposals" :key="item.url">
                            <div class="p-3 hover:bg-indigo-50 flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-800" x-text="item.title"></span>
                                <form action="{{ route('playlists.songs.add-scraped', $playlist) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="source_url" :value="item.url">
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-gray-800 text-xs font-semibold py-1 px-2 rounded">
                                        Extraire & Importer
                                    </button>
                                </form>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Liste des chansons déjà importées --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Chansons dans la Playlist ({{ $playlist->songs->count() }})</h3>
                
                <div class="space-y-6">
                    @forelse ($playlist->songs as $song)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 shadow-sm">
                            
                            {{-- Ligne du haut : Artiste, Morceau, Lien Source & Bouton Supprimer --}}
                            <div class="flex flex-row items-center justify-between w-full pb-3 mb-4 border-b border-gray-200 gap-4">
                                <div class="text-base text-gray-800">
                                    <span class="font-bold text-gray-900">Artist:</span> {{ $song->artist_name }} 
                                    <span class="text-gray-400 mx-2">|</span> 
                                    <span class="font-bold text-gray-900">Song:</span> {{ $song->song_name }}
                                </div>
                                
                                <div class="flex items-center space-x-4 shrink-0">
                                    @if($song->source_url)
                                        <a href="{{ $song->source_url }}" target="_blank" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 underline flex items-center gap-1">
                                            URL
                                        </a>
                                    @endif

                                    <form action="{{ route('songs.destroy', $song) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette chanson de la playlist ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-red text-xs font-semibold py-1.5 px-3 rounded shadow-sm transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Section des Facts (1 ligne vide entre chaque grâce aux \n\n et whitespace-pre-line) --}}
                            <div class="text-sm text-gray-700 whitespace-pre-line leading-relaxed pl-1">
                                {{ $song->song_infos }}
                            </div>

                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Aucune chanson dans cette playlist pour le moment.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>