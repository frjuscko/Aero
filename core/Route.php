<?php

namespace Desinova\Aero;

/**
 * --------------------------------------------------------------------------
 * Aero Framework - Composant Route
 * --------------------------------------------------------------------------
 * Représente une entité de navigation unique avec ses configurations de nom
 * et ses couches de middlewares assignées.
 */
class Route
{
    public string $method;
    public string $path;
    public $callback;
    public ?string $name = null;
    public array $middlewares = [];

    public function __construct(string $method, string $path, $callback)
    {
        $this->method = strtolower($method);
        $this->path = $path;
        $this->callback = $callback;
    }

    /**
     * Assigne un nom unique à la route pour l'inversion d'URL
     */
    public function name(string $name): self
    {
        $this->name = $name;
        Application::getInstance()->router->registerNamedRoute($name, $this);
        return $this; 
    }

    /**
     * Attache une ou plusieurs barrières (Middlewares) à cette route
     */
    public function middleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->middlewares = array_merge($this->middlewares, $middleware);
        } else {
            $this->middlewares[] = $middleware;
        }
        return $this;
    }
}