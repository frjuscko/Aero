<?php

namespace Desinova\Aero;

/**
 * --------------------------------------------------------------------------
 * Aero Framework - Classe Maîtresse Application
 * --------------------------------------------------------------------------
 * Gère le cycle de vie de l'application, l'isolation du système de rendu,
 * le moteur de template et le traitement global des exceptions.
 */
class Application 
{
    public static Application $instance;
    public Request $request;
    public Router $router;
    public Database $db;
    
    protected array $config = [];
    protected array $viewParams = [];
    protected array $sections = [];
    protected array $sectionStack = [];
    private ?string $layoutName = null;

    public function __construct()
    {
        self::$instance = $this;

        // Activation du gestionnaire de crash personnalisé d'Aero
        set_exception_handler([$this, 'handleException']);
        set_error_handler(function($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        // Initialisation immédiate des composants de base indispensables
        $this->request = new Request();
        $this->router = new Router($this->request);
    }

    /**
     * Point d'accès global (Singleton) pour récupérer l'instance d'Aero
     */
    public static function getInstance(): Application
    {
        return self::$instance;
    }

    /**
     * Injection optionnelle de la configuration et de la base de données
     */
    public function config(array $config)
    {
        $this->config = $config;
        $this->db = new Database($config['db'] ?? []);
    }

    /**
     * Lance la résolution de la route et affiche le résultat
     */
    public function run() 
    {
        $response = $this->router->resolve();

        if ($response instanceof \Desinova\Aero\Response) {
            if (method_exists($response, 'send')) {
                $response->send();
            }
            return;
        }

        echo $response;
    }

    /**
     * Rendu isolé d'une page HTML avec le compilateur de template Aero
     */
    public function render(string $view, array $params = [])
    {
        // Normalisation Windows/Linux des séparateurs de dossiers
        $view = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $view);

        // Données utilisateur globales (si le module Auth existe)
        if (class_exists('\Desinova\Aero\Auth')) {
            $params['currentUser'] = \Desinova\Aero\Auth::user();
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Fusion persistante des paramètres de la vue vers le Layout parent
        if (empty($this->layoutName)) {
            $this->viewParams = $params;
        } else {
            $params = array_merge($this->viewParams, $params);
        }

        // Interception et nettoyage des variables flash de session
        $flashParams = $_SESSION['_flash_params'] ?? [];
        $params = array_merge($flashParams, $params);
        unset($_SESSION['_flash_params']);

        extract($params, EXTR_SKIP);

        // --- NOUVEAUX CHEMINS AJUSTÉS À NOTRE ARCHITECTURE ---
        $viewFile  = __DIR__ . "/../pages/$view.php";
        $cacheFile = __DIR__ . "/../cache/" . $view . ".cache.php";
        // -----------------------------------------------------

        $exactCacheSubDir = dirname($cacheFile);
        if (!is_dir($exactCacheSubDir)) {
            mkdir($exactCacheSubDir, 0777, true);
        }
        
        // Compilation à la volée si le fichier cache est obsolète ou inexistant
        if (file_exists($viewFile)) {
            if (!file_exists($cacheFile) || filemtime($viewFile) > filemtime($cacheFile)) {
                $content = file_get_contents($viewFile);

                // Directives personnalisées et balises de confiance
                $content = preg_replace('/\{\{\s*encrypt\:(.+?)\s*\}\}/', '<?php echo encrypt($1); ?>', $content);
                $content = preg_replace('/\{\!\!\s*(.+?)\s*\!\!\}/', '<?php echo $1; ?>', $content);

                // Compilation des structures de contrôle (Style Blade)
                $content = preg_replace('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '<?php \Desinova\Aero\Application::getInstance()->startLayout("$1"); ?>', $content);
                $content = preg_replace('/@section\s*\(\s*[\'"](.+?)[\'"]\s*,\s*[\'"](.+?)[\'"]\s*\)/', '<?php \Desinova\Aero\Application::getInstance()->setSectionShort("$1", "$2"); ?>', $content);
                $content = preg_replace('/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)(?!\s*,)/', '<?php \Desinova\Aero\Application::getInstance()->startSection("$1"); ?>', $content);
                $content = preg_replace('/@endsection\b/', '<?php \Desinova\Aero\Application::getInstance()->endSection(); ?>', $content);
                
                $content = preg_replace('/@content\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '<?php echo \Desinova\Aero\Application::getInstance()->getSection("$1"); ?>', $content);
                $content = preg_replace('/@content\b(?!\s*\()/', '<?php echo \Desinova\Aero\Application::getInstance()->getSection("content"); ?>', $content);
                $content = preg_replace('/@include\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '<?php echo \Desinova\Aero\Application::getInstance()->render("$1", get_defined_vars()); ?>', $content);

                $content = preg_replace('/@php/', '<?php', $content);
                $content = preg_replace('/@endphp/', '?>', $content);

                $content = preg_replace('/@foreach\s*\((.+?)\)/', '<?php foreach($1): ?>', $content);
                $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);

                $content = preg_replace('/@if\s*\((.+)\)/', '<?php if($1): ?>', $content);
                $content = preg_replace('/@elseif\s*\((.+)\)/', '<?php elseif($1): ?>', $content);
                $content = preg_replace('/@else/', '<?php else: ?>', $content);
                $content = preg_replace('/@endif/', '<?php endif; ?>', $content);

                $content = preg_replace('/@switch\s*\((.+?)\)/', '<?php switch($1): ?>', $content);
                $content = preg_replace('/@case\s*\((.+?)\)/', '<?php case $1: ?>', $content);
                $content = preg_replace('/@default/', '<?php default: ?>', $content);
                $content = preg_replace('/@break/', '<?php break; ?>', $content);
                $content = preg_replace('/@endswitch/', '<?php endswitch; ?>', $content);

                // Échappement automatique sécurisé des variables (Toujours en dernier !)
                $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars((string)($1) ?? "", ENT_QUOTES); ?>', $content);

                file_put_contents($cacheFile, $content);
            }
        } else {
            throw new \Exception("La page [<strong>$view</strong>] n'existe pas dans le dossier pages/.");
        }

        // Exécution de la vue compilée dans une fonction anonyme isolée (Scope protégé)
        ob_start();
        (function() use ($cacheFile, $params) {
            extract($params);
            include $cacheFile;
        })();
        $output = ob_get_clean();

        // Enchaînement automatique si la page réclame un Layout parent
        if ($this->layoutName) {
            $layout = $this->layoutName;
            $this->layoutName = null; 
            $output = $this->render($layout, $this->viewParams);
            $this->viewParams = []; 
            return $output;
        }

        return $output;
    }

    // --- INTERFACES INTERNES DU MOTEUR DE TEMPLATE ---
    public function startLayout(string $layout) { $this->layoutName = $layout; }
    public function setSectionShort(string $name, string $value) { $this->sections[$name] = $value; }
    public function startSection(string $name) { $this->sectionStack[] = $name; ob_start(); }
    public function endSection() {
        if (empty($this->sectionStack)) throw new \Exception("Directive @endsection sans bloc d'ouverture.");
        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }
    public function getSection(string $name): string { return $this->sections[$name] ?? ''; }

    /**
     * Capture de crash globale de l'application
     */
    public function handleException(\Throwable $exception)
    {
        http_response_code(500);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $message = "<strong>Fatal Error:</strong> " . $exception->getMessage() .
                   "<br><small>Fichier : " . $exception->getFile() . " à la ligne " . $exception->getLine() . "</small>";

        try {
            echo $this->render('errors/error', ['code' => 500, 'message' => $message]);
        } catch (\Throwable $e) {
            echo "<h1>Défaut Critique Aero (500)</h1><p>$message</p>";
        }
        exit;
    }
}