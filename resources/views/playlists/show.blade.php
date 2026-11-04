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

            {{-- Moteur de Recherche Intelligente Songfacts avec validation par bouton --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg border-2 border-indigo-100" 
                x-data="{
                    artist: '',
                    song: '',
                    loading: false,
                    directData: null,
                    proposals: [],
                    errorMessage: '',
                    search() {
                        this.errorMessage = '';
                        
                        if (!this.artist.trim() || !this.song.trim()) {
                            this.errorMessage = 'Veuillez remplir à la fois l\'artiste et le morceau.';
                            return;
                        }

                        this.loading = true;
                        this.directData = null;
                        this.proposals = [];

                        const params = new URLSearchParams({
                            artist: this.artist.trim(),
                            song: this.song.trim()
                        });

                        fetch('{{ route('search.songfacts') }}?' + params.toString())
                            .then(res => res.json())
                            .then(data => {
                                this.loading = false;
                                if (data.direct) {
                                    this.directData = data.data;
                                    this.proposals = [];
                                } else {
                                    this.directData = null;
                                    this.proposals = data.proposals || [];
                                    if (this.proposals.length === 0) {
                                        this.errorMessage = 'Aucun résultat direct ni suggestion trouvée sur Songfacts.';
                                    }
                                }
                            })
                            .catch(() => { 
                                this.loading = false; 
                                this.errorMessage = 'Une erreur s\'est produite lors de la recherche.';
                            });
                    }
                }">
                
                <h3 class="text-lg font-bold text-gray-800 mb-1">🔍 Recherche & Extraction Songfacts</h3>
                <p class="text-sm text-gray-500 mb-4">Renseignez l'artiste et le morceau puis cliquez sur <b>Rechercher</b> (ou appuyez sur Entrée).</p>

                {{-- Formulaire d'envoi synchrone des deux champs --}}
                <form @submit.prevent="search()" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Artiste / Groupe</label>
                            <input type="text" 
                                x-model="artist" 
                                placeholder="Ex: Imagine Dragons / Coldplay" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Morceau</label>
                            <input type="text" 
                                x-model="song" 
                                placeholder="Ex: Sharks / Yellow" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    {{-- Message d'erreur local --}}
                    <template x-if="errorMessage">
                        <p class="text-xs text-red-600 font-semibold" x-text="errorMessage"></p>
                    </template>

                    <div class="flex justify-end">
                        <button type="submit" 
                                :disabled="loading" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded-md text-sm shadow-sm transition flex items-center gap-2">
                            <span x-show="!loading">🔍 Rechercher sur Songfacts</span>
                            <span x-show="loading">Recherche en cours...</span>
                        </button>
                    </div>
                </form>

                {{-- Résultat Direct Trouvé --}}
                <template x-if="directData">
                    <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
                        <div class="font-bold text-green-900 text-base" x-text="directData.artist_name + ' — ' + directData.song_name"></div>
                        <p class="text-xs text-gray-600 mt-1 line-clamp-3" x-text="directData.song_infos"></p>
                        <form action="{{ route('playlists.songs.add-scraped', $playlist) }}" method="POST" class="mt-3">
                            @csrf
                            <input type="hidden" name="source_url" :value="directData.source_url">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1.5 px-4 rounded text-xs shadow-sm">
                                + Ajouter cette chanson à la playlist
                            </button>
                        </form>
                    </div>
                </template>

                {{-- Propositions d'autocomplétion si non direct --}}
                <template x-if="!directData && proposals.length > 0">
                    <div class="mt-4 bg-white border rounded-md shadow-lg divide-y">
                        <div class="p-2 bg-gray-50 text-xs font-semibold text-gray-500">Suggestions associées :</div>
                        <template x-for="item in proposals" :key="item.url">
                            <div class="p-3 hover:bg-indigo-50 flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-800" x-text="item.title"></span>
                                <form action="{{ route('playlists.songs.add-scraped', $playlist) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="source_url" :value="item.url">
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold py-1 px-3 rounded">
                                        Extraire & Importer
                                    </button>
                                </form>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Liste des chansons déjà importées --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg mt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Chansons dans la Playlist ({{ $playlist->songs->count() }})</h3>
                
                <div class="space-y-6">
                    @forelse ($playlist->songs as $song)
                        @php
                            $rawInfos = $song->song_infos;
                            if (is_array($rawInfos)) {
                                $facts = $rawInfos;
                            } else {
                                $facts = array_filter(array_map('trim', explode("\n\n", (string) $rawInfos)));
                            }
                        @endphp

                        {{-- Composant Carte Morceau avec Alpine.js --}}
                        <div x-data="{ open: true }" class="bg-gray-50 border border-gray-200 rounded-lg p-5 shadow-sm">
                            
                            {{-- Ligne du haut --}}
                            <div class="flex flex-row items-center justify-between w-full transition-all duration-150 gap-4"
                                 :class="open ? 'pb-3 mb-4 border-b border-gray-200' : 'pb-0 mb-0 border-b-0'">
                                
                                {{-- Artiste et Titre (espace à gauche via pl-3) --}}
                                <div class="text-base text-gray-800 truncate pl-3">
                                    <span class="font-bold text-gray-900">Artist:</span> {{ $song->artist_name }} 
                                    <span class="text-gray-400 mx-2">|</span> 
                                    <span class="font-bold text-gray-900">Song:</span> {{ $song->song_name }}
                                </div>
                                
                                {{-- Actions à droite --}}
                                <div class="flex items-center space-x-3 shrink-0">
                                    @if($song->source_url)
                                        <a href="{{ $song->source_url }}" target="_blank" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 underline flex items-center gap-1">
                                            URL
                                        </a>
                                    @endif

                                    <form action="{{ route('songs.destroy', $song) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette chanson de la playlist ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold py-1.5 px-3 rounded shadow-sm transition">
                                            Delete
                                        </button>
                                    </form>

                                    {{-- Bouton Flèche Agrandir / Réduire --}}
                                    <button type="button" 
                                            @click="open = !open" 
                                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 p-1.5 rounded transition flex items-center justify-center"
                                            title="Agrandir / Réduire">
                                        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                </div>

                            </div>

                            {{-- Bloc des Facts (Affiché / Masqué avec Alpine) --}}
                            <div x-show="open" class="space-y-4 pl-3">
                                @forelse($facts as $fact)
                                    <div class="flex items-start gap-2.5">
                                        <span class="text-indigo-500 font-bold text-base leading-none select-none">•</span>
                                        <p class="text-sm text-gray-700 leading-relaxed">{{ preg_replace('/^[\s•\-\*]+/u', '', $fact) }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-400 italic">Aucune anecdote disponible pour cette chanson.</p>
                                @endforelse
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