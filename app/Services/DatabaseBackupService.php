<?php

namespace App\Services;

use App\Models\Playlist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class DatabaseBackupService
{
    /**
     * Generate a SQL dump (INSERT INTO) of a playlist's data in .txt format and then compress it into a .zip file.
     * @param Playlist $playlist the playlist to backup
     * @return string Absolute path to the generated .zip file
     * @throws RuntimeException if the backup process fails
     */
    public function generatePlaylistBackup(Playlist $playlist): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException("PHP extension 'zip' is not installed. Please install it to enable database backup functionality.");
        }

        $sqlDump  = "-- Save BDD PolyPhony — Playlist : {$playlist->playlist_name} (ID: {$playlist->id})\n";
        $sqlDump .= "-- Generated on : " . date('Y-m-d H:i:s') . "\n\n";

        // 1. Export of the main row in the `playlists` table
        $playlistRows = DB::table('playlists')->where('id', $playlist->id)->get();
        $sqlDump .= $this->buildInsertStatements('playlists', $playlistRows);

        // 2. Export of all the tables related to this playlist via `playlist_id`
        $relatedTables = [
            'playlist_user',
            'songs',
            'singers',
            'timelines',
            'documents',
        ];

        foreach ($relatedTables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $rows = DB::table($tableName)
                ->where('playlist_id', $playlist->id)
                ->get();

            $sqlDump .= $this->buildInsertStatements($tableName, $rows);
        }

        // Creation of the storage/app/backups directory if it doesn't exist
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $slugName = Str::slug($playlist->playlist_name) ?: 'playlist';
        $fileName = "backup_playlist_{$playlist->id}_{$slugName}_" . date('Y-m-d_His');
        $txtPath  = "{$backupDir}/{$fileName}.txt";
        $zipPath  = "{$backupDir}/{$fileName}.zip";

        // Write the raw SQL script into the .txt file
        if (file_put_contents($txtPath, $sqlDump) === false) {
            throw new RuntimeException("Impossible d'écrire le fichier de sauvegarde temporaire (.txt).");
        }

        // Create the ZIP archive and delete the temporary .txt file after archiving
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($txtPath);
            throw new RuntimeException("Impossible de créer l'archive ZIP de sauvegarde.");
        }

        $zip->addFile($txtPath, "{$fileName}.txt");
        $zip->close();

        // Delete the temporary .txt file after archiving
        @unlink($txtPath);

        return $zipPath;
    }

    /**
     * For each row, it generates an INSERT statement with the appropriate values, handling NULLs, booleans, and escaping strings.
     * @param string $tableName The name of the table for which to generate INSERT statements
     * @param iterable $rows The rows of data to be inserted
     * @return string The generated SQL INSERT statements
     */
    private function buildInsertStatements(string $tableName, iterable $rows): string
    {
        $output = '';

        foreach ($rows as $row) {
            $arrayRow = (array) $row;

            if ($output === '') {
                $output .= "-- Data of table `{$tableName}`\n";
            }

            $attributes = implode('`, `', array_keys($arrayRow));

            $values = implode(', ', array_map(function ($value) {
                if (is_null($value)) {
                    return 'NULL';
                }
                if (is_bool($value)) {
                    return $value ? '1' : '0';
                }
                if (is_int($value) || is_float($value)) {
                    return (string) $value;
                }

                $escaped = str_replace(
                    ["\\", "\0", "\n", "\r", "'", '"', "\x1a"],
                    ["\\\\", "\\0", "\\n", "\\r", "\\'", '\\"', "\\Z"],
                    (string) $value
                );

                return "'{$escaped}'";
            }, array_values($arrayRow)));

            $output .= "INSERT INTO `{$tableName}` (`{$attributes}`) VALUES ({$values});\n";
        }

        return $output !== '' ? $output . "\n" : '';
    }
}