<?php

namespace Desinova\Aero;

/**
 * --------------------------------------------------------------------------
 * Aero Framework - Composant Response
 * --------------------------------------------------------------------------
 * Prend en charge l'envoi des en-têtes (headers), la gestion des sessions
 * flash éphémères, ainsi que la sérialisation des sorties JSON pour l'API.
 */
class Response
{
    protected string $url = '/';

    /**
     * Prépare une redirection vers une URL spécifique
     */
    public function redirect(string $url): self
    {
        $this->url = $url;
        return $this; 
    }

    /**
     * Prépare une redirection vers la page précédente (Historique HTTP)
     */
    public function redirectBack(): self
    {
        $this->url = $_SERVER['HTTP_REFERER'] ?? '/';
        return $this;
    }

    /**
     * Envoie la redirection brute vers le navigateur
     */
    public function send(): void
    {
        header("Location: " . $this->url);
        exit;
    }

    /**
     * Stocke des données temporaires en session puis déclenche la redirection
     */
    public function with(string $type, string $message)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['_flash_params'] = [
            'type'    => $type,
            'message' => $message
        ];

        $this->send();
    }

    /**
     * Génère une sortie JSON propre et interrompt le script pour éviter les parasites HTML
     */
    public function json(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}