<?php

/**
 * --------------------------------------------------------------------------
 * Configuration Globale d'Aero
 * --------------------------------------------------------------------------
 */

return [
    // Configuration de la Base de Données (Optionnelle)
    'db' => [
        'host'     => 'localhost',
        'dbname'   => '',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // Middlewares globaux à exécuter à chaque requête
    'middlewares' => [
        // Exemple : \App\Middlewares\AuthMiddleware::class
    ],

    // Métadonnées de l'application
    'env' => 'development', // development ou production
];