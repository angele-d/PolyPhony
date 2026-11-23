# Cahier des Charges Technico-Fonctionnel — Projet PolyPhony

---

## 1. Contexte & Présentation du Projet

### 1.1 Contexte Académique
Le projet **PolyPhony** s'inscrit dans le cadre du cours de *Web Programming* (FERIT Osijek). Il synthétise l'ensemble des compétences développées au travers des 3 travaux pratiques fondamentaux :
- **Exercice 1** : Programmation orientée objet avancée en PHP, requêtes cURL/Sockets, parsing HTML via SimpleHTMLDom et persistance dans MySQL/MariaDB avec architecture d'interfaces.
- **Exercice 2** : Manipulation du système de fichiers, génération de scripts de sauvegarde SQL au format `.txt` compressés en `.zip`, parsing de documents XML structurés, et chiffrement/déchiffrement de documents multimédias via les bibliothèques cryptographiques (Mcrypt/OpenSSL).
- **Exercice 3** : Développement d'un socle d'application web complet sous le framework Laravel (version 5.8+ ou ultérieure), incluant l'authentification native, les migrations, la gestion des projets collaboratifs avec matrice de droits granulaires (Administrateur vs Contributeur), et l'internationalisation (multilinguisme).

### 1.2 Objectif de l'Application
PolyPhony est une plateforme collaborative de gestion **Playlists** musicales enrichies (curation de morceaux, informations sur les chanteurs/artistes, frises chronologiques et décryptages de paroles). 

L'application offre un double niveau d'interaction :
1. **Un moteur d'extraction dynamique** capable de scrapé, chercher et convertir les contenus de la plateforme *Songfacts.com* (titres de chansons, histoires de morceaux, fiches d'artistes/chanteurs, frises chronologiques)
2. **Un espace de travail collaboratif sous Laravel** permettant à un Administrateur de créer une Playlist, d'y inviter des Contributeurs, d'y ajouter des musiques (scrapées ou manuelles), des fiches d'artistes, des jalons chronologiques, ainsi que de sécuriser des partitions/maquettes chiffrées.

---

## 2. Spécifications des Besoins

### 2.1 Besoins Fonctionnels (BF)

#### BF-1 : Scraping, Indexation et Recherche Intelligente Songfacts
* **Scraping Discographique** : Récupération de la liste des chansons d'un artiste à partir de `https://www.songfacts.com/songs/[artist-slug]`.
* **Scraping Explicatif** : Extraction du sens des paroles, détails de production et anecdotes depuis `https://www.songfacts.com/facts/[artist-slug]/[music-slug]`.
* **Scraping Chronologique (Timeline)** : Récupération des événements marquants depuis `https://calendar.songfacts.com/timeline/[artist-slug]`.
* **Recherche & Auto-slugification** : Saisie libre par l'utilisateur (ex: `"Coldplay Yellow"`). Le système génère automatiquement les slugs d'URL nettoyés (`coldplay`, `yellow`) et vérifie l'existence des pages. Si la correspondance directe échoue, une recherche sur l'annuaire Songfacts est exécutée et renvoie des propositions en JSON.

#### BF-2 : Espace Collaboratif Projets / Playlists (Laravel)
* **Authentification** : Inscription, connexion, déconnexion des utilisateurs avec hashage des mots de passe.
* **Gestion des Playlists** :
  * Création d'une playlist avec : `playlist_name`, attribution automatique du rôle d'**Administrateur** au créateur.
  * Ajout/invitation de **Contributeurs** parmi les utilisateurs inscrits sur la plateforme.
* **Matrice de Permissions Granulaires** :
  - **Administrateur** : Modification des informations de la playlist, suppression de la playlist, gestion de la liste des contributeurs, suppression de chansons/chanteurs/timelines.
  - **Contributeur** : Ajout de chansons (songs), de chanteurs (singers) et de timelines au sein de la playlist, édition des informations. *Interdiction de supprimer la playlist ou de supprimer des éléments ajoutés par d'autres*


#### BF-3 : Coffre-fort Documentaire Chiffré & Imports XML (Exercice 2)
* **Stockage Sécurisé** : Téléversement de fichiers joints (partitions PDF, visuels PNG/JPEG, démos audio MP3). Seule la version chiffrée (OpenSSL / AES-256) est conservée sur le serveur.
* **Déchiffrement à la volée** : Seuls les membres autorisés de la playlist (Administrateur et Contributeurs) peuvent déchiffrer et télécharger le document original.
* **Importation XML de Musiques** : Parsing d'un fichier XML (`songs.xml`) permettant de générer automatiquement des fiches d'artistes ou d'intervenants avec photo, email, biographie et coordonnées.
* **Sauvegarde & Archivage BDD** : Génération d'un dump SQL sous forme de requêtes `INSERT INTO` enregistré dans un fichier `.txt`, puis compression en archive `.zip`.

#### BF-4 : Multilinguisme
* Basculement dynamique de l'ensemble de l'interface utilisateur entre le **Français** et l'**Anglais** (extensible au Croate).

---

### 2.2 Besoins Non-Fonctionnels (BNF)

* **Sécurité** : Protection CSRF sur tous les formulaires Laravel, échappement systématique XSS, requêtes SQL préparées (PDO), chiffrement au repos fort pour le coffre-fort documentaire.
* **Fiabilité du Parser** : Utilisation d'un agent utilisateur (User-Agent) conforme lors des requêtes cURL, et gestion des exceptions HTTP (codes 404, 500, 301).
* **Maintenabilité POO** : Découplage strict entre la logique métier du parser (via des Interfaces PHP) et les contrôleurs Laravel.
* **Interactivité Front-End** : Réponses JSON fluides pour l'auto-complétion de la barre de recherche.

---

## 3. Étude Technique du Scraping & de la Recherche Intelligente

### 3.1 Structure des URLs Cibles sur Songfacts

Songfacts s'appuie sur un système de routage très prévisible basé sur le slugging en minuscules avec tirets :

```
1. Discographie complète : https://www.songfacts.com/songs/[artist-slug]
   Exemple: https://www.songfacts.com/songs/coldplay

2. Fiche Chanson & Sens  : https://www.songfacts.com/facts/[artist-slug]/[song-slug]
   Exemple: https://www.songfacts.com/facts/coldplay/yellow

3. Frise Chronologique   : https://calendar.songfacts.com/timeline/[artist-slug]
   Exemple: https://calendar.songfacts.com/timeline/coldplay
```

### 3.2 Balisage HTML & Sélecteurs DOM

Le tableau suivant récapitule les balises HTML cibles identifiées pour l'extraction via `SimpleHTMLDom` :

| Page Cible | Élement à Extraire | Balise / Sélecteur HTML | Transformation PHP / Regex |
| :--- | :--- | :--- | :--- |
| **`/songs/[artist]`** | Liste des chansons | `ul.browse-list-music li a` | Extraire le texte de l'ancre `<a>` et le lien `href` |
| **`/facts/[artist]/[song]`** | Titre de la chanson | `h1.songfact-title` ou `.header-title` | `trim(strip_tags(...))` |
| **`/facts/[artist]/[song]`** | Explications & Sens | `div.songfact-text` ou `.fact-content` | Extraction du bloc HTML / conversion texte |
| **`/timeline/[artist]`** | Événements / Années | `.timeline-event` / `.event-year` | Association Date + Texte explicatif |

### 3.3 Moteur de Recherche Intelligente et Résolution Automatique

Le moteur de recherche suit un workflow à 2 niveaux pour garantir une expérience utilisateur fluide :

```
                    ┌─────────────────────────────────────────┐
                    │ Saisie utilisateur (ex: "Coldplay Yellow")│
                    └────────────────────┬────────────────────┘
                                         │
                                         ▼
                    ┌─────────────────────────────────────────┐
                    │ Nettoyage & Slugification en PHP        │
                    │ $artist = "coldplay", $song = "yellow"  │
                    └────────────────────┬────────────────────┘
                                         │
                                         ▼
                    ┌─────────────────────────────────────────┐
                    │  Test HEAD / GET HTTP cURL direct vers   │
                    │  songfacts.com/facts/coldplay/yellow    │
                    └────────────────────┬────────────────────┘
                                         │
                   ┌─────────────────────┴─────────────────────┐
                   │                                           │
         [ Code HTTP 200 OK ]                        [ Code HTTP 404 / Non trouvé ]
                   │                                           │
                   ▼                                           ▼
┌──────────────────────────────────────┐    ┌──────────────────────────────────────┐
│ Extraction directe via SimpleHTMLDom │    │ Execution d'une recherche cURL sur   │
│ et affichage du formulaire d'ajout   │    │ songfacts.com/search/songs/[query]   │
└──────────────────────────────────────┘    └──────────────────┬───────────────────┘
                                                               │
                                                               ▼
                                            ┌──────────────────────────────────────┐
                                            │ Extraction des liens de propositions │
                                            │ et retour JSON à l'auto-complétion   │
                                            └──────────────────────────────────────┘
```

#### Algorithme de Slugification (PHP/Laravel) :
```php
use Illuminate\Support\Str;

public function generateSlugs(string $input): array
{
    // Séparation optionnelle si l'utilisateur entre "Artiste - Chanson"
    $parts = explode('-', $input);
    if (count($parts) === 2) {
        return [
            'artist' => Str::slug(trim($parts[0])),
            'song'   => Str::slug(trim($parts[1]))
        ];
    }
    
    return ['query' => Str::slug($input)];
}
```

---

## 4. Modélisation POO & Schéma de Base de Données

### 4.1 Architecture POO (Respect de l'Exercice 1)

L'accès aux données scrapées est encapsulé derrière une interface PHP `iRadio` (renommée `iSongRepository` dans l'implémentation métier) conformément aux exigences de l'exercice 1.

```
┌────────────────────────────────────────────────────────┐
│                   <<interface>>                        │
│                  iSongRepository                       │
├────────────────────────────────────────────────────────┤
│ + create(string $url): self                            │
│ + save(): bool                                         │
│ + read(): array                                        │
│ + update(int $id, array $data): bool                   │
│ + delete(int $id): bool                                │
└───────────────────────────▲────────────────────────────┘
                            │
                            │ implements
┌───────────────────────────┴────────────────────────────┐
│                        Song                            │
├────────────────────────────────────────────────────────┤
│ + $song_name : string                                  │
│ + $song_infos : string                                 │
│ + $song_url : string                                   │
│ + $added_at : string                                   │
│ + $updated_at : string                                 │
│ + $artist_name : string                                │
├────────────────────────────────────────────────────────┤
│ + create(string $url): self                            │
│ + save(): bool                                         │
│ + read(): array                                        │
│ + update(int $id, array $data): bool                   │
│ + delete(int $id): bool                                │
└────────────────────────────────────────────────────────┘
```

### 4.2 Schéma Relational MySQL / MariaDB

Le schéma comprend les tables d'authentification Laravel, les playlists, la liste des contributeurs, ainsi que les tables dédiées aux contenus (songs, singers, timelines) et aux documents chiffrés.

```sql
-- Table des Utilisateurs (Laravel Native)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des Playlists (Créées par un Administrateur)
CREATE TABLE playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_name VARCHAR(255) NOT NULL,
    administrator_id INT NOT NULL,            -- Référence vers l'utilisateur Administrateur
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_playlist_admin FOREIGN KEY (administrator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Pivot des Contributeurs de la Playlist
CREATE TABLE playlist_contributor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    user_id INT NOT NULL,                     -- Utilisateur invité en tant que Contributeur
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pc_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des Musiques / Chansons (Songs)
CREATE TABLE songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,                 -- Playlist à laquelle appartient le morceau
    song_name VARCHAR(255) NOT NULL,          -- Nom de la chanson (name)
    artist_name VARCHAR(255) NOT NULL,        -- Nom de l'artiste / groupe
    song_infos LONGTEXT NULL,                 -- Explications / Informations sur le morceau (infos)
    source_url VARCHAR(500) NOT NULL,           -- URL source (url)
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date d'ajout (added_at)
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_song_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des Chanteurs / Artistes (Singers)
CREATE TABLE singers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,                 -- Playlist associée
    singer_name VARCHAR(255) NOT NULL,        -- Nom du chanteur/artiste
    bio_summary TEXT NULL,                    -- Résumé / Biographie
    picture_url VARCHAR(500) NULL,            -- URL de la photo / visuel
    source_url VARCHAR(500) NOT NULL,            -- URL source Songfacts Singer
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_singer_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des Événements Chronologiques (Timelines)
CREATE TABLE timelines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,                 -- Playlist associée
    artist_name VARCHAR(255) NOT NULL,        -- Artiste concerné
    event_year VARCHAR(50) NOT NULL,          -- Année ou date de l'événement
    event_description TEXT NOT NULL,          -- Description de l'événement
    source_url VARCHAR(500) NULL,             -- URL source Songfacts Timeline
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_timeline_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des Documents Chiffrés Joints (Partitions, Démos, Contrats)
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    encrypted_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Modules de Chiffrement, Traitement XML et Backup (Exercice 2)

### 5.1 Sécurité & Chiffrement des Documents

Afin d'assurer la confidentialité des maquettes audio ou des partitions déposées dans les projets, les fichiers ne sont jamais stockés en clair sur le disque du serveur.

#### Algorithme de Chiffrement (OpenSSL / AES-256-CBC) :
```php
<?php

namespace App\Services;

class CryptoManager
{
    private static string $cipher = "AES-256-CBC";

    private static function getKey(): string
    {
        return substr(hash('sha256', config('app.key')), 0, 32);
    }

    public static function encryptFile(string $sourcePath, string $destPath): bool
    {
        $fileData = file_get_contents($sourcePath);
        if ($fileData === false) return false;

        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($fileData, self::$cipher, self::$getKey(), 0, $iv);
        if ($encrypted === false) return false;

        return file_put_contents($destPath, $iv . $encrypted) !== false;
    }

    public static function decryptFileContent(string $filePath): string|false
    {
        $fileData = file_get_contents($filePath);
        if ($fileData === false) return false;

        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($fileData, 0, $ivLength);
        $encryptedData = substr($fileData, $ivLength);

        return openssl_decrypt($encryptedData, self::$cipher, self::$getKey(), 0, $iv);
    }
}
```

---

### 5.2 Structure du Fichier XML & Module de Importation

Le projet intègre un module d'importation de profils d'intervenants ou de musiciens via des fichiers XML au format unifié `songs.xml` 

#### Exemple de Format XML (`songs.xml`) :
```xml
<?xml version="1.0" encoding="UTF-8"?>
<songs>
    <song>
        <songname>So What</songname>
        <artistname>Miles Davis</artistname>
        <songinfos>Une composition emblématique du jazz modal, issue de l'album "Kind of Blue" (1959).</songinfos>
        <songurl>https://www.songfacts.com/facts/miles-davis/so-what</songurl>
    </song>
    <song>
        <songname>Feeling Good</songname>
        <artistname>Nina Simone</artistname>
        <songinfos>Une chanson emblématique de Nina Simone, interprétée dans plusieurs langues.</songinfos>
        <songurl>https://www.songfacts.com/facts/nina-simone/feeling-good</songurl>
    </song>
    <song>
        <songname>Strange Fruit</songname>
        <artistname>Bessie Smith</artistname>
        <songinfos>Une chanson puissante sur la lynchage en Amérique du Sud.</songinfos>
        <songurl>https://www.songfacts.com/facts/bessie-smith/strange-fruits</songurl>
    </song>
    <song>
        <songname>Four</songname>
        <artistname>John Coltrane</artistname>
        <songinfos>Une composition emblématique de John Coltrane, issue de l'album "A Love Supreme" (1965).</songinfos>
        <songurl>https://www.songfacts.com/facts/john-coltrane/four</songurl>
    </song>
</songs>
```

#### Script d'Analyse et d'Affichage XML (PHP/Laravel) :
```php
<?php

namespace App\Services;

use App\Models\Song;

class XmlImportService
{
    public function importSongsFromXml(string $xmlFilePath, int $playlistId): int
    {
        if (!file_exists($xmlFilePath)) {
            return 0;
        }

        $xmlContent = simplexml_load_file($xmlFilePath);
        $importedCount = 0;

        foreach ($xmlContent->song as $songNode) {
            Song::create([
                'playlist_id' => $playlistId,
                'song_name'   => (string) $songNode->songname,
                'artist_name' => (string) $songNode->artistname,
                'song_infos'  => (string) $songNode->songinfos,
                'song_url'    => (string) $songNode->songurl,
                'added_at'    => (string) $songNode->added_at,
            ]);
            $importedCount++;
        }

        return $importedCount;
    }
}
```

---

### 5.3 Moteur de Sauvegarde BDD (.txt & .zip)

Conformément au sujet du Télétravail 2 (Task 1), l'application embarque un script de backup capable d'exporter les tables de la base de données au format texte brut d'instructions SQL `INSERT INTO`, puis de compresser le résultat en `.zip`.

#### Implémentation du Script de Backup :
```php
public function generateDatabaseBackup(string $databaseName): string {
    $tables = DB::select('SHOW TABLES');
    $tableKey = "Tables_in_" . $databaseName;
    $sqlDump = "-- Sauvegarde BDD PolyPhony : " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $tableObj) {
        $tableName = $tableObj->$tableKey;
        $rows = DB::table($tableName)->get();

        foreach ($rows as $row) {
            $arrayRow = (array) $row;
            $attributes = implode('`, `', array_keys($arrayRow));
            $values = implode("', '", array_map('addslashes', array_values($arrayRow)));
            
            $sqlDump .= "INSERT INTO {$tableName} (`{$attributes}`) VALUES ('{$values}');\n";
        }
    }

    // Écriture dans le fichier .txt
    $fileName = "backup_" . $databaseName . "_" . time();
    $txtPath = storage_path("app/backups/{$fileName}.txt");
    $zipPath = storage_path("app/backups/{$fileName}.zip");

    file_put_contents($txtPath, $sqlDump);

    // Archiving ZIP
    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
        $zip->addFile($txtPath, "{$fileName}.txt");
        $zip->close();
        unlink($txtPath); // Suppression du .txt temporaire
    }

    return $zipPath;
}
```

---

## 6. Architecture Laravel, Matrice de Droits & Multilinguisme

### 6.1 Matrix des Autorisations (Policies Laravel)

Le système différencie formellement les prérogatives de l'**Administrateur** de celles des **Contributeurs** :

| Action / Fonctionnalité | Administrateur | Contributeur | Autre |
| :--- | :---: | :---: | :---: |
| **Supprimer la Playlist** | **OUI** | NON | NON |
| **Modifier Métadonnées (Nom, Dates, Budget)** | **OUI** | NON | NON |
| **Ajouter / Retirer des Membres d'Équipe** | **OUI** | NON | NON |
| **Supprimer n'importe quelle Chanson** | **OUI** | NON | NON |
| **Ajouter une Chanson (Scrapée ou Manuel)** | **OUI** | **OUI** | NON |
| **Éditer les Tâches (`jobs_completed`)** | **OUI** | **OUI** | NON |
| **Téléverser / Télécharger un Document** | **OUI** | **OUI** | NON |
| **Consulter la Playlist** | **OUI** | **OUI** | Selon visibilité |

#### Exemple d'Implémentation d'une Policy Laravel (`ProjectPolicy.php`) :
```php
namespace App\Policies;

use App\User;
use App\Project;

class ProjectPolicy
{
    // Modifier le projet ou supprimer la playlist : Reservé à l'administrateur
    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    // Retirer une chanson : Reservé à l'administrateur
    public function removeSong(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    // Ajouter une chanson : Reservé à l'administrateur OU aux contributeurs
    public function addSong(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $project->members->contains($user->id);
    }

    // Supprimer une chanson: Reservé à l'administrateur OU au contributeur si c'est lui qui l'a ajouté
}
```

---

### 6.2 Localisation & Support Multilingue (Exercice 3)

La localisation est gérée nativement via le composant `Illuminate\Translation\Translator`. Les dictionnaires de langue sont stockés dans les dossiers `resources/lang/fr` et `resources/lang/en`.

#### Fichier `resources/lang/fr/project.php` :
```php
return [
    'title'            => 'Gestionnaire de Playlists & Décryptage',
];
```

#### Fichier `resources/lang/en/project.php` :
```php
return [
    'title'            => 'Playlist & Decrypt Management',
];
```

Liste non exhaustive

---

## 7. Tableau Récapitulatif de Conformité aux Exercices

| Exigence du Cours / Exercice | Module PolyPhony Correspondant | Statut & Conformité |
| :--- | :--- | :---: |
| **Ex. 1 : Interface `iRadio` & Métriques** | Interface `iSongRepository` et classe `Song` (`GraduateThesis`) | **Conforme** |
| **Ex. 1 : Parsing cURL & SimpleHTMLDom** | Extraction de `Songfacts.com` (`/songs/`, `/facts/`, `/timeline/`) | **Conforme** |
| **Ex. 1 : Persistance MySQL / MariaDB** | Table `Songs` et gestion PDO/Eloquent | **Conforme** |
| **Ex. 2 : Backup BDD (`.txt` vers `.zip`)** | Engine `generateDatabaseBackup()` avec requêtes `INSERT INTO` | **Conforme** |
| **Ex. 2 : Parser XML (`LV2.xml`)** | Module `parseSongsXML()` pour fiches artistes | **Conforme** |
| **Ex. 2 : Chiffrement de Documents** | Module `CryptoManager` (OpenSSL AES-256) pour PDF/PNG/MP3 | **Conforme** |
| **Ex. 3 : Auth Laravel & Migrations** | Système Auth native, migrations `playlists`, `users`, pivots | **Conforme** |
| **Ex. 3 : Projets & Membres d'Équipe** | Architecture N:M (`playlist_contributor`) avec PM créateur | **Conforme** |
| **Ex. 3 : Droits PM vs Membre (`jobs_completed`)** | Policies Laravel : Membre ajoute des morceaux et édite l'avancement ; PM gère le projet et supprime | **Conforme** |
| **Ex. 3 : Multilinguisme** | Traduction intégrale FR / EN via Laravel Localization | **Conforme** |
