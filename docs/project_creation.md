Here is presented the process of creating this PolyPhony project. The project is based on Laravel framework and uses MariaDB as database management system.

## Step 1: Initialize Laravel Project

```bash
composer create-project laravel/laravel polyphony
mv polyphony/* polyphony/.* . 2>/dev/null
rm -rf polyphony
```

The 2 last lines are used to move all the files from the `polyphony` folder to the current directory and then remove the empty `polyphony` folder.

## Step 2: Configure the Database

Connect to your MariaDB server and create a new database for the project. You can use the following SQL command in your terminal or in a database management tool like DBeaver/phpMyAdmin:

```sql
CREATE DATABASE polyphony CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Step 3: Configure the .env File

Update the `.env` file with the database configuration:

```dotenv
APP_NAME=PolyPhony
APP_URL=http://localhost:8000

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=polyphony
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Then generate the application key by running the following command:

```bash
php artisan key:generate
```

## Step 4: Install the authentification system

Install Laravel Breeze:

```bash
composer require laravel/breeze --dev
php artisan breeze:install
```

Compile the assets and run the migrations:

```bash
npm install
npm run dev
php artisan migrate
```

## Step 5: Install packages for Scraping

Install the simplehtmldom library:

```bash
composer require simplehtmldom/simplehtmldom:2.0-RC2
```

## Step 6: Generate Laravel Composants via Artisan

```bash
# 1: Main models + migrations
php artisan make:model Playlist -m
php artisan make:model Song -m
php artisan make:model Singer -m
php artisan make:model Timeline -m
php artisan make:model Document -m

# 2: Migration for the pivot table of the team (Playlist <-> User)
php artisan make:migration create_playlist_user_table --create=playlist_user

# 3: Application controllers
php artisan make:controller PlaylistController
php artisan make:controller SongController
php artisan make:controller SingerController
php artisan make:controller TimelineController
php artisan make:controller SearchController
php artisan make:controller DocumentController
php artisan make:controller BackupController
php artisan make:controller XmlImportController

# 4: Policy for differencing the access to the projects (Administrateur vs Contributeurs)
php artisan make:policy PlaylistPolicy --model=Playlist
```

## Step 7: Create repositories for personalized OOP and encrypted storage

```bash
mkdir -p app/Contracts app/Services
mkdir -p storage/app/encrypted_docs
```

Contracts will contain:
- iSongRepository.php

Services will contain:
- Song.php

## Step 8: Create the database tables

Change the migration files in `database/migrations` to add the necessary fields for the `projects`, `songs`, `singers`, `timelines`, and `documents` tables. Then run the migrations:

```bash
php artisan migrate:fresh
```
