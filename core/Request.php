<?php

namespace Desinova\Aero;

/**
 * --------------------------------------------------------------------------
 * Aero Framework - Composant Request
 * --------------------------------------------------------------------------
 * Capture, nettoie et isole les données entrantes du protocole HTTP.
 * Gère l'extraction des URLs sous XAMPP/Apache et l'asynchronisme JSON.
 */
class Request
{
    /**
     * Récupère la méthode HTTP en minuscules (get, post, put, etc.)
     */
    public function getMethode(): string
    {
        return strtolower($_SERVER['REQUEST_METHOD'] ?? 'get');
    }

    /**
     * Récupère le chemin épuré de l'URL pour le Router
     */
    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        // 1. Isolation des paramètres de requête (on coupe tout après le '?')
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        // 2. NETTOYAGE DU SOUS-DOSSIER RACINE (Spécial XAMPP / Sous-dossiers Apache)
        // Si le projet est dans htdocs/MonProjet, $_SERVER['SCRIPT_NAME'] vaut /MonProjet/index.php
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $baseDir = rtrim(dirname($scriptName), '/');

        // Si on est dans un sous-dossier, on le retire du début de l'URI
        if (!empty($baseDir) && strpos($path, $baseDir) === 0) {
            $path = substr($path, strlen($baseDir));
        }

        // 3. Nettoyage final de sécurité
        $path = str_replace('/index.php', '', $path);

        return '/' . ltrim($path, '/');
    }

    /**
     * Récupère et sécurise l'intégralité des données reçues (GET, POST, ou JSON Payload)
     */
    public function getBody(): array
    {
        $body = [];
        $method = $this->getMethode();

        // 1. Capture des données transmises par l'URL (GET)
        if ($method === 'get') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        // 2. Capture des données transmises par Formulaire (POST)
        if ($method === 'post') {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        // 3. Capture des flux asynchrones (Fetch / Axios / application/json)
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $jsonRaw = file_get_contents('php://input');
            $jsonData = json_decode($jsonRaw, true);
            if (is_array($jsonData)) {
                foreach ($jsonData as $key => $value) {
                    $body[$key] = is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
                }
            }
        }

        return $body;
    }
}