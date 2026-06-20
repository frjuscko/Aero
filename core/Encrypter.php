<?php

namespace Desinova\Aero;

class Encrypter
{
    protected string $key;
    protected string $cipher = 'AES-256-CBC';

    public function __construct()
    {
        // Une clé secrète de 32 caractères pour l'AES-256
        // En production, elle viendra d'un fichier .env
        $this->key = hash('sha256', 'Aero-Secret-Salt-Key-2026!');
    }

    /**
     * Chiffrer une donnée (ex: 14 -> token)
     */
    public function encrypt($value): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt((string)$value, $this->cipher, $this->key, 0, $iv);
        
        // On combine l'IV et le texte chiffré, puis on encode en Base64 URL-Safe
        $token = base64_encode($iv . $encrypted);
        return str_replace(['+', '/', '='], ['-', '_', ''], $token); // Clean pour les URLs
    }

    /**
     * Déchiffrer un token (ex: token -> 14)
     */
    /**
     * Déchiffrer un token (ex: token -> 14)
     */
    /**
     * Déchiffrer un token (ex: token -> 14)
     */
    public function decrypt(string $token)
    {
        // 1. On remet les caractères de remplacement + et /
        $token = str_replace(['-', '_'], ['+', '/'], $token);
        
        // 2. On recalcule et on rajoute le padding '=' manquant pour le Base64 strict
        $rem = strlen($token) % 4;
        if ($rem) {
            $token .= str_repeat('=', 4 - $rem);
        }
        
        $decoded = base64_decode($token, true);
        
        if (!$decoded) {
            return null;
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        if (strlen($decoded) <= $ivLength) {
            return null;
        }
        
        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);
        
        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key, 0, $iv);
        
        return $decrypted !== false ? $decrypted : null;
    }
}