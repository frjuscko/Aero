<?php

namespace Desinova\Aero;

/**
 * --------------------------------------------------------------------------
 * Aero Framework - Composant Router (Le Dispatcheur)
 * --------------------------------------------------------------------------
 * Analyse l'URL entrante, applique les filtres regex pour les paramètres 
 * dynamiques, valide la sécurité, exécute les middlewares et livre les données.
 */
class Router
{
    protected Request $request;
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected array $middlewareMap = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Map les alias aux classes physiques de middleware
     */
    public function aliasMiddleware(array $middlewares): self
    {
        $this->middlewareMap = array_merge($this->middlewareMap, $middlewares);
        return $this;
    }

    public function get(string $path, $callback): Route
    {
        $route = new Route('get', $path, $callback);
        $this->routes['get'][$path] = $route;
        return $route;
    }

    public function post(string $path, $callback): Route
    {
        $route = new Route('post', $path, $callback);
        $this->routes['post'][$path] = $route;
        return $route;
    }

    public function registerNamedRoute(string $name, Route $route)
    {
        $this->namedRoutes[$name] = $route;
    }

    /**
     * Résout un nom de route en URL absolue / relative
     */
    public function getUrlByName(string $name): string
    {
        if (!isset($this->namedRoutes[$name])) {
            return '#route-invalide';
        }
        return $this->namedRoutes[$name]->path;
    }

    /**
     * Analyse et exécute la route correspondante à la requête courante
     */
    public function resolve()
    {
        $path = $this->request->getPath();
        $methode = strtolower($this->request->getMethode());

        $route = $this->routes[$methode][$path] ?? false;
        $params = [];

        // 1. Analyse dynamique par Expressions Régulières si aucune correspondance exacte
        if ($route === false) {
            foreach ($this->routes[$methode] ?? [] as $routePath => $routeObj) {
                // Traduction des patterns type /profil/{id} en regex
                $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $routePath);
                $pattern = "#^" . $pattern . "$#";

                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches); // Supprime l'URL complète capturée
                    $params = $matches;
                    $route = $routeObj;
                    break;
                }
            }
        }

        // 2. Gestion de l'erreur 404 (Route inconnue)
        if ($route === false) {
            http_response_code(404);
            return Application::getInstance()->render('errors/error', [
                'code' => 404,
                'message' => "Le chemin spécifié n'existe pas ou a été déplacé sur une autre orbite."
            ]);
        }

        // 3. Déchiffrement automatique de sécurité pour les paramètres d'ID
        if (!empty($params)) {
            $encryptedId = $params[0];
            $decryptedId = decrypt($encryptedId); // Appel à ton helper global de chiffrement

            if ($decryptedId === null) {
                http_response_code(400);
                return Application::getInstance()->render('errors/error', [
                    'code' => 400,
                    'message' => "La signature de sécurité de l'URL est invalide ou a expiré."
                ]);
            }
            $params[0] = $decryptedId; // Injection de la vraie valeur nettoyée
        }

        // 4. Interception et exécution de la pile de Middlewares assignés
        foreach ($route->middlewares as $middlewareAlias) {
            if (isset($this->middlewareMap[$middlewareAlias])) {
                $middlewareClass = $this->middlewareMap[$middlewareAlias];
                $middlewareInstance = new $middlewareClass();
                
                $response = $middlewareInstance->execute();
                
                // Si un middleware renvoie un objet réponse (ex: redirection), on coupe le circuit court
                if ($response !== null) {
                    return $response;
                }
            }
        }

        // 5. Résolution du Callback
        $callback = $route->callback;

        // Cas d'une closure / fonction anonyme
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        }

        // Cas d'un tableau de contrôleur [Classe, Méthode]
        if (is_array($callback)) {
            $controllerClass = $callback[0];
            $methodeName = $callback[1];

            if (!class_exists($controllerClass) || !method_exists($controllerClass, $methodeName)) {
                http_response_code(500);
                return Application::getInstance()->render('errors/error', [
                    'code' => 500,
                    'message' => "La méthode <strong>{$methodeName}</strong> est introuvable dans le contrôleur <strong>{$controllerClass}</strong>."
                ]);
            }
            
            $controller = new $controllerClass();
            $callback[0] = $controller;

            return call_user_func_array($callback, $params);
        }

        return "Type de callback non supporté.";
    }
}