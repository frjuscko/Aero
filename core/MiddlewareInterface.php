<?php

namespace Desinova\Aero;

/**
 * --------------------------------------------------------------------------
 * Aero Framework - Contrat de Middleware
 * --------------------------------------------------------------------------
 * Définit la structure obligatoire pour toutes les barrières de sécurité
 * et filtres HTTP interceptés par le Router.
 */
interface MiddlewareInterface
{
    /**
     * Exécute la logique de filtrage ou de vérification.
     *
     * @return mixed Renvoie un objet Response (redirection/erreur) pour couper 
     * le circuit, ou null pour laisser passer la requête.
     */
    public function execute();
}