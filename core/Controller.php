<?php

namespace Desinova\Aero;

/**
 * --------------------------------------------------------------------------
 * Aero Framework - Classe Mère Controller
 * --------------------------------------------------------------------------
 * Fournit des raccourcis fluides aux contrôleurs applicatifs pour le rendu
 * de pages, les réponses JSON ou l'accès aux requêtes HTTP.
 */
class Controller
{
    /**
     * Raccourci vers le moteur de rendu d'Aero
     */
    public function render(string $view, array $params = [])
    {
        return Application::getInstance()->render($view, $params);
    }

    /**
     * Raccourci vers l'émetteur de réponses JSON
     */
    protected function json(array $data, int $status = 200): void
    {
        (new Response())->json($data, $status);
    }
}