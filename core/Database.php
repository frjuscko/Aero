<?php

namespace Desinova\Aero;

use PDO;
use PDOException;

/**
 * --------------------------------------------------------------------------
 * Aero Framework - Composant Database
 * --------------------------------------------------------------------------
 * Gère la connexion à la base de données via une stratégie de Lazy Loading.
 * Évite les connexions inutiles si le projet n'utilise pas de stockage SQL.
 */
class Database
{
    protected ?PDO $pdo = null;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Récupère l'instance PDO unique (Lazy Loading)
     */
    public function pdo(): PDO
    {
        // Si la connexion a déjà été établie, on la réutilise (Singleton d'instance)
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            $dbConfig = $this->config;

            // Construction dynamique de la chaîne de connexion DSN
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $dbConfig['driver']   ?? 'mysql',
                $dbConfig['host']     ?? '127.0.0.1',
                $dbConfig['port']     ?? '3306',
                $dbConfig['dbname']   ?? '',
                $dbConfig['charset']  ?? 'utf8mb4'
            );

            // Instanciation de PDO avec les clés alignées sur config/app.php
            $this->pdo = new PDO(
                $dsn,
                $dbConfig['username'] ?? 'root', // Harmonisé avec config/app.php
                $dbConfig['password'] ?? ''
            );

            // Configurations de sécurité et de robustesse pour Aero
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // L'erreur est encapsulée pour être interceptée par Application::handleException
            throw new \Exception("Erreur critique de base de données : " . $e->getMessage(), (int)$e->getCode(), $e);
        }

        return $this->pdo;
    }
}