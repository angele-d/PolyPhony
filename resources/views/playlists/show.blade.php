<x-app-layout>
    <x-slot name="header">
        <div x-data="{ openTeamModal: {{ $errors->has('email') ? 'true' : 'false' }}, openSettingsModal: {{ $errors->has('playlist_name') ? 'true' : 'false' }} }"
             class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                        {{ $playlist->playlist_name }}
                    </h2>

                    @can('manageMembers', $playlist)
                        <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                            Administrateur
                        </span>
                    @else
                        <span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                            Contributeur
                        </span>
                    @endcan
                </div>
                <p class="text-sm text-gray-500 mt-0.5">
                    Administrateur : <span class="font-medium text-gray-700">{{ $playlist->administrator->name }}</span>
                </p>
                @if($playlist->description)
                    <p class="text-xs text-gray-500 mt-1">{{ $playlist->description }}</p>
                @endif
            </div>

            <div class="flex items-center gap-3">
                {{-- Bouton Modale Équipe --}}
                <button type="button"
                        @click="openTeamModal = true"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm transition flex items-center gap-2">
                    <span>👥 Équipe ({{ $playlist->contributors->count() + 1 }})</span>
                </button>

                {{-- Bouton Export SQL (.zip) --}}
                @can('view', $playlist)
                    <form action="{{ route('playlists.backup', $playlist) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm transition flex items-center gap-2"
                                title="Exporter les données SQL de cette playlist (.txt dans un .zip)">
                            <span>💾 Export SQL (.zip)</span>
                        </button>
                    </form>
                @endcan

                {{-- Bouton Paramètres (Administrateur uniquement, si routes update/destroy déclarées) --}}
                @can('update', $playlist)
                    @if(Route::has('playlists.update') || Route::has('playlists.destroy'))
                        <button type="button"
                                @click="openSettingsModal = true"
                                class="bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300 px-3 py-2 rounded-md text-sm font-semibold transition">
                            ⚙️ Paramètres
                        </button>
                    @endif
                @endcan

                <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm font-semibold">
                    &larr; Retour au tableau de bord
                </a>
            </div>

            {{-- Modale de Gestion des Contributeurs --}}
            <div x-show="openTeamModal"
                 style="display: none;"
                 class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-gray-900/50 p-4"
                 @keydown.escape.window="openTeamModal = false">

                <div @click.away="openTeamModal = false"
                     class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 space-y-5">

                    <div class="flex justify-between items-center border-b pb-3">
                        <h3 class="text-lg font-bold text-gray-900">👥 Équipe de la Playlist</h3>
                        <button type="button" @click="openTeamModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">
                            &times;
                        </button>
                    </div>

                    {{-- Formulaire d'invitation (Administrateur uniquement) --}}
                    @can('manageMembers', $playlist)
                        <form action="{{ route('playlists.members.store', $playlist) }}" method="POST" class="space-y-2">
                            @csrf
                            <label class="block text-xs font-bold text-gray-700 uppercase">Inviter un contributeur (e-mail)</label>
                            <div class="flex gap-2">
                                <input type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       required
                                       placeholder="exemple@domaine.com"
                                       class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-md shadow-sm">
                                    Inviter
                                </button>
                            </div>
                            @error('email')
                                <p class="text-xs text-red-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </form>
                    @endcan

                    {{-- Liste des membres actuels --}}
                    <div class="space-y-3 pt-2">
                        <h4 class="text-xs font-bold text-gray-500 uppercase">Membres ayant accès</h4>

                        {{-- Ligne Administrateur --}}
                        <div class="flex items-center justify-between p-3 bg-indigo-50/60 border border-indigo-100 rounded-md">
                            <div>
                                <div class="text-sm font-bold text-gray-900">{{ $playlist->administrator->name }}</div>
                                <div class="text-xs text-gray-500">{{ $playlist->administrator->email }}</div>
                            </div>
                            <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                Administrateur
                            </span>
                        </div>

                        {{-- Liste des Contributeurs --}}
                        @forelse($playlist->contributors as $contributor)
                            <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-md">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $contributor->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $contributor->email }}
                                        @if($contributor->pivot && $contributor->pivot->added_at)
                                            • Ajouté le {{ \Carbon\Carbon::parse($contributor->pivot->added_at)->format('d/m/Y') }}
                                        @endif
                                    </div>
                                </div>

                                @can('manageMembers', $playlist)
                                    <form action="{{ route('playlists.members.destroy', [$playlist, $contributor]) }}"
                                          method="POST"
                                          onsubmit="return confirm('Retirer {{ $contributor->name }} de cette playlist ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-2.5 py-1 rounded shadow-sm">
                                            Retirer
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-500 font-medium">Contributeur</span>
                                @endcan
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic">Aucun contributeur invité pour le moment.</p>
                        @endforelse
                    </div>

                    <div class="flex justify-end border-t pt-3">
                        <button type="button" @click="openTeamModal = false" class="bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-semibold px-4 py-2 rounded">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modale Paramètres & Suppression (Administrateur uniquement) --}}
            @can('update', $playlist)
                @if(Route::has('playlists.update') || Route::has('playlists.destroy'))
                    <div x-show="openSettingsModal"
                         style="display: none;"
                         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-gray-900/50 p-4"
                         @keydown.escape.window="openSettingsModal = false">

                        <div @click.away="openSettingsModal = false"
                             class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 space-y-5">

                            <div class="flex justify-between items-center border-b pb-3">
                                <h3 class="text-lg font-bold text-gray-900">⚙️ Paramètres de la Playlist</h3>
                                <button type="button" @click="openSettingsModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">
                                    &times;
                                </button>
                            </div>

                            @if(Route::has('playlists.update'))
                                <form action="{{ route('playlists.update', $playlist) }}" method="POST" class="space-y-4">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nom de la playlist</label>
                                        <input type="text"
                                               name="playlist_name"
                                               value="{{ old('playlist_name', $playlist->playlist_name) }}"
                                               required
                                               class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('playlist_name')
                                            <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Description</label>
                                        <textarea name="description"
                                                  rows="3"
                                                  class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $playlist->description) }}</textarea>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-md shadow-sm">
                                            Enregistrer les modifications
                                        </button>
                                    </div>
                                </form>
                            @endif

                            @can('delete', $playlist)
                                @if(Route::has('playlists.destroy'))
                                    <div class="border-t pt-4 flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-red-700">Supprimer la playlist</p>
                                            <p class="text-xs text-gray-500">Cette action est irréversible et supprimera tout son contenu.</p>
                                        </div>
                                        <form action="{{ route('playlists.destroy', $playlist) }}"
                                              method="POST"
                                              onsubmit="return confirm('Voulez-vous vraiment supprimer définitivement cette playlist ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-2 rounded shadow-sm">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endcan

                            <div class="flex justify-end border-t pt-3">
                                <button type="button" @click="openSettingsModal = false" class="bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-semibold px-4 py-2 rounded">
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            @endcan
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

            {{-- Moteur de Recherche Intelligente Songfacts (Chanson / Artiste) : Administrateur et Contributeurs --}}
            @can('contribute', $playlist)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg border-2 border-indigo-100" 
                    x-data="{
                        type: 'song',
                        artist: '',
                        song: '',
                        loading: false,
                        directData: null,
                        proposals: [],
                        errorMessage: '',
                        search() {
                            this.errorMessage = '';
                            
                            if (this.type === 'song' && (!this.artist.trim() || !this.song.trim())) {
                                this.errorMessage = 'Veuillez remplir à la fois l\'artiste et le morceau.';
                                return;
                            }

                            if (this.type === 'artist' && !this.artist.trim()) {
                                this.errorMessage = 'Veuillez remplir le nom de l\'artiste.';
                                return;
                            }

                            this.loading = true;
                            this.directData = null;
                            this.proposals = [];

                            const params = new URLSearchParams({
                                type: this.type,
                                artist: this.artist.trim(),
                                song: this.type === 'song' ? this.song.trim() : ''
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
                    <p class="text-sm text-gray-500 mb-4">Choisissez ce que vous souhaitez rechercher puis remplissez les informations correspondantes.</p>

                    {{-- Radio Buttons pour le choix du type de recherche --}}
                    <div class="flex items-center space-x-6 mb-4">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" x-model="type" value="song" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm font-semibold text-gray-700">Chanson</span>
                        </label>

                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" x-model="type" value="artist" @change="song = ''" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm font-semibold text-gray-700">Artiste</span>
                        </label>
                    </div>

                    {{-- Formulaire d'envoi synchrone --}}
                    <form @submit.prevent="search()" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Artiste / Groupe</label>
                                <input type="text" 
                                    x-model="artist" 
                                    placeholder="Ex: Coldplay, Imagine Dragons..." 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Morceau</label>
                                <input type="text" 
                                    x-model="song" 
                                    :disabled="type === 'artist'"
                                    :placeholder="type === 'artist' ? 'Désactivé pour la recherche Artiste' : 'Ex: Yellow, Sharks...'" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    :class="type === 'artist' ? 'bg-gray-100 cursor-not-allowed opacity-60' : 'bg-white'">
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
                            <div class="font-bold text-green-900 text-base" 
                                x-text="directData.type === 'artist' ? 'Artiste : ' + directData.singer_name : directData.artist_name + ' — ' + directData.song_name">
                            </div>
                            <p class="text-xs text-gray-600 mt-1 line-clamp-3" 
                            x-text="directData.bio_summary || directData.song_infos">
                            </p>
                            <form action="{{ route('playlists.songs.add-scraped', $playlist) }}" method="POST" class="mt-3">
                                @csrf
                                <input type="hidden" name="source_url" :value="directData.source_url">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1.5 px-4 rounded text-xs shadow-sm">
                                    + Ajouter à la playlist
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
            @endcan

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

                                    @can('contribute', $playlist)
                                        <form action="{{ route('songs.destroy', $song) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette chanson de la playlist ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold py-1.5 px-3 rounded shadow-sm transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endcan

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

            {{-- Liste des Artistes dans la Playlist --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg mt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Artistes dans la Playlist ({{ $playlist->singers->count() }})</h3>
                
                <div class="space-y-6">
                    @forelse ($playlist->singers as $singer)
                        @php
                            $facts = array_filter(array_map('trim', explode("\n\n", (string) $singer->bio_summary)));
                        @endphp

                        <div x-data="{ open: true }" class="bg-indigo-50/40 border border-indigo-200 rounded-lg p-5 shadow-sm">
                            
                            {{-- Ligne du haut --}}
                            <div class="flex flex-row items-center justify-between w-full transition-all duration-150 gap-4"
                                :class="open ? 'pb-3 mb-4 border-b border-indigo-200' : 'pb-0 mb-0 border-b-0'">
                                
                                <div class="flex items-center gap-3 text-base text-gray-800 truncate pl-3">
                                    @if($singer->picture_url)
                                        <img src="{{ $singer->picture_url }}" alt="{{ $singer->singer_name }}" class="w-8 h-8 rounded-full object-cover shrink-0 border border-indigo-200">
                                    @endif
                                    <div>
                                        <span class="font-bold text-gray-900">Artist:</span> {{ $singer->singer_name }}
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3 shrink-0">
                                    @if($singer->source_url)
                                        <a href="{{ $singer->source_url }}" target="_blank" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 underline flex items-center gap-1">
                                            URL
                                        </a>
                                    @endif

                                    @can('contribute', $playlist)
                                        <form action="{{ route('singers.destroy', $singer) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cet artiste de la playlist ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold py-1.5 px-3 rounded shadow-sm transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endcan

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

                            {{-- Bloc des Facts Artiste --}}
                            <div x-show="open" class="space-y-4 pl-3">
                                @forelse($facts as $fact)
                                    <div class="flex items-start gap-2.5">
                                        <span class="text-indigo-500 font-bold text-base leading-none select-none">•</span>
                                        <p class="text-sm text-gray-700 leading-relaxed">{{ preg_replace('/^[\s•\-\*]+/u', '', $fact) }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-400 italic">Aucune anecdote disponible pour cet artiste.</p>
                                @endforelse
                            </div>

                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Aucun artiste dans cette playlist pour le moment.</p>
                    @endforelse
                </div>
            </div>

            {{-- Liste et Upload sécurisé des Documents (PDF, PNG, MP3) --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg mt-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">
                            🔒 Documents & Médias sécurisés ({{ $playlist->documents->count() }})
                        </h3>
                        <p class="text-xs text-gray-500">
                            Fichiers chiffrés via OpenSSL (AES-256-CBC) avant stockage sur le serveur.
                        </p>
                    </div>
                </div>

                {{-- Formulaire d'upload réservé à l'Administrateur et aux Contributeurs --}}
                @can('contribute', $playlist)
                    <form action="{{ route('playlists.documents.store', $playlist) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="mb-6 p-4 bg-gray-50 border border-dashed border-gray-300 rounded-lg">
                        @csrf
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex-1">
                                <label for="document" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                    Joindre un fichier (PDF, PNG, MP3 — max 20 Mo)
                                </label>
                                <input type="file"
                                       id="document"
                                       name="document"
                                       accept=".pdf,.png,.mp3,application/pdf,image/png,audio/mpeg"
                                       required
                                       class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                @error('document')
                                    <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 px-5 rounded-md shadow-sm transition shrink-0">
                                🔐 Chiffrer & Importer
                            </button>
                        </div>
                    </form>
                @endcan

                {{-- Liste des documents chiffrés --}}
                <div class="space-y-3">
                    @forelse ($playlist->documents as $document)
                        @php
                            $ext = strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION));
                            $badgeColor = match ($ext) {
                                'PDF' => 'bg-red-100 text-red-800',
                                'PNG' => 'bg-sky-100 text-sky-800',
                                'MP3' => 'bg-purple-100 text-purple-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp

                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="px-2.5 py-1 rounded text-xs font-bold shrink-0 {{ $badgeColor }}">
                                    {{ $ext ?: 'FILE' }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">
                                        {{ $document->original_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Chiffré AES-256 • Ajouté le {{ \Carbon\Carbon::parse($document->added_at)->format('d/m/Y à H:i') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 shrink-0">
                                {{-- Lecteur audio direct pour les MP3 déchiffrés à la volée --}}
                                @if ($document->mime_type === 'audio/mpeg')
                                    <audio controls preload="none" class="h-8 max-w-[220px]">
                                        <source src="{{ route('documents.download', ['document' => $document, 'inline' => 1]) }}" type="audio/mpeg">
                                    </audio>
                                @else
                                    {{-- Ouverture sécurisée dans un nouvel onglet (PDF / PNG) --}}
                                    <a href="{{ route('documents.download', ['document' => $document, 'inline' => 1]) }}"
                                       target="_blank"
                                       class="text-xs font-semibold text-gray-700 hover:text-indigo-700 underline">
                                        Aperçu
                                    </a>
                                @endif

                                {{-- Lien de téléchargement sécurisé (déchiffrement serveur) --}}
                                <a href="{{ route('documents.download', $document) }}"
                                   class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold py-1.5 px-3 rounded shadow-sm transition">
                                    ⬇ Télécharger
                                </a>

                                {{-- Suppression (Admin & Contributeurs) --}}
                                @can('contribute', $playlist)
                                    <form action="{{ route('documents.destroy', $document) }}"
                                          method="POST"
                                          onsubmit="return confirm('Voulez-vous vraiment supprimer ce document chiffré ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold py-1.5 px-3 rounded shadow-sm transition">
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Aucun document chiffré dans cette playlist pour le moment.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>