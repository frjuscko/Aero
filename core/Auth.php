<?php

namespace Desinova\Aero;

class Auth
{
    // Stocke l'instance de l'utilisateur en mémoire pour éviter de refaire une requête SQL inutile
    protected static ?Model $user = null;

    /**
     * Tenter de connecter un utilisateur avec ses identifiants
     * @param string $email L'email ou l'identifiant saisi
     * @param string $password Le mot de passe en clair
     * @param string $modelClass Le modèle à utiliser (User::class par défaut)
     */
    public static function attempt(string $email, string $password, string $modelClass = 'App\Models\User'): bool
    {
        // 1. On cherche l'utilisateur par son email via notre QueryBuilder
        $user = $modelClass::where('whatsapp', $email)->first();

        // 2. Si l'utilisateur existe, on vérifie son mot de passe haché
        if ($user && password_verify($password, $user->password)) {
            self::login($user);
            return true;
        }

        return false;
    }

    /**
     * Connecter directement une instance d'utilisateur (après une inscription par exemple)
     */
    public static function login(Model $user)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // On stocke l'ID et la classe du modèle en session
        $_SESSION['_auth_user_id'] = $user->getKey();
        $_SESSION['_auth_model_class'] = get_class($user);
        
        self::$user = $user;
    }

    /**
     * Récupérer l'instance de l'utilisateur actuellement connecté
     */
    public static function user(): ?Model
    {
        // Si on l'a déjà récupéré durant cette requête, on le renvoie directement
        if (self::$user !== null) {
            return self::$user;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['_auth_user_id'] ?? null;
        $modelClass = $_SESSION['_auth_model_class'] ?? null;

        // Si on a un ID au chaud en session, on demande à l'ORM de charger le modèle
        if ($userId && $modelClass) {
            self::$user = $modelClass::find($userId);
            return self::$user;
        }

        return null;
    }

    /**
     * Vérifier si un utilisateur est connecté (renvoie true ou false)
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Déconnecter proprement l'utilisateur
     */
    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // On nettoie les variables d'authentification en session
        unset($_SESSION['_auth_user_id']);
        unset($_SESSION['_auth_model_class']);
        
        // On réinitialise la mémoire volatile
        self::$user = null;
    }
}