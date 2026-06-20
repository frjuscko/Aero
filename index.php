<?php

/**
 * --------------------------------------------------------------------------
 * Aero Framework - Porte d'entrée principale
 * --------------------------------------------------------------------------
 * Toutes les requêtes HTTP convergent vers ce fichier grâce à la réécriture 
 * d'URL. C'est ici que le framework s'éveille, s'initialise et s'exécute.
 */

// 1. Chargement de l'Autoloader Composer (PSR-4)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// 2. Chargement des Helpers globaux du Framework
if (file_exists(__DIR__ . '/core/helpers.php')) {
    require_once __DIR__ . '/core/helpers.php';
}

// 3. Instanciation du cœur de l'application (Conteneur de Services)
use Desinova\Aero\Application;

$app = new Application();

// 4. Injection optionnelle de la configuration globale
if (file_exists(__DIR__ . '/config/app.php')) {
    $config = require_once __DIR__ . '/config/app.php';
    $app->config($config);
}

// 5. Chargement du registre des routes de l'application
if (file_exists(__DIR__ . '/config/web.php')) {
    $router = $app->router; // On crée la variable ici pour la rendre dispo dans le fichier inclus !
    require_once __DIR__ . '/config/web.php';
}

// 6. Lancement du réacteur : résolution et affichage de la réponse HTTP
$app->run();