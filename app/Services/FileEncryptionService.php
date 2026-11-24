<?php

namespace App\Services;

use RuntimeException;

class FileEncryptionService
{
    private const CIPHER = 'aes-256-cbc';

    /**
     * Give a binary key of 32 bytes derived from APP_KEY.
     */
    private function getKey(): string
    {
        $appKey = (string) config('app.key');

        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            if ($decoded !== false) {
                return hash('sha256', $decoded, true);
            }
        }

        if (empty($appKey)) {
            throw new RuntimeException(__('The APP_KEY is missing in the configuration.'));
        }

        return hash('sha256', $appKey, true);
    }

    /**
     * Encrypts binary content using OpenSSL (AES-256-CBC + HMAC).
     */
    public function encrypt(string $plainContent): string
    {
        $key = $this->getKey();
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLength);

        $cipherText = openssl_encrypt(
            $plainContent,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($cipherText === false) {
            throw new RuntimeException(__('OpenSSL file encryption failed.'));
        }

        // Signature HMAC pour vérifier que le fichier chiffré n'a pas été altéré
        $hmac = hash_hmac('sha256', $iv . $cipherText, $key, true);

        return $hmac . $iv . $cipherText;
    }

    /**
     * Decrypts previously encrypted content using OpenSSL (AES-256-CBC + HMAC).
     */
    public function decrypt(string $encryptedPayload): string
    {
        $key = $this->getKey();
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $hmacLength = 32; // SHA-256 produit 32 octets

        if (strlen($encryptedPayload) <= ($hmacLength + $ivLength)) {
            throw new RuntimeException(__('Corrupted or invalid encrypted file.'));
        }

        $hmac = substr($encryptedPayload, 0, $hmacLength);
        $iv = substr($encryptedPayload, $hmacLength, $ivLength);
        $cipherText = substr($encryptedPayload, $hmacLength + $ivLength);

        // Vérification de l'intégrité avant déchiffrement
        $expectedHmac = hash_hmac('sha256', $iv . $cipherText, $key, true);
        if (!hash_equals($expectedHmac, $hmac)) {
            throw new RuntimeException(__('File integrity compromised (invalid HMAC).'));
        }

        $plainContent = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($plainContent === false) {
            throw new RuntimeException(__('OpenSSL file decryption failed.'));
        }

        return $plainContent;
    }
}