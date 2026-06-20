<?php

use Desinova\Aero\Application;
use Desinova\Aero\Encrypter;
use Desinova\Aero\Response;
use GreenApi\RestApi\GreenApiClient;

if (!function_exists('view')) {
    /**
     * Raccourci global pour effectuer le rendu d'une page
     */
    function view(string $view, array $params = [])
    {
        return Application::getInstance()->render($view, $params);
    }
}

if (!function_exists('route')) {
    /**
     * Génère une URL absolue complète à partir du nom d'une route
     * Parfaitement compatible avec les sous-dossiers XAMPP/Apache et l'index racine.
     */
    function route(string $name, array $params = []): string
    {
        $url = Application::getInstance()->router->getUrlByName($name);

        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $url = str_replace('{' . $key . '}', (string)$value, $url);
            }
        }

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') 
                    ? 'https' : 'http';
        
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Calcul propre du sous-dossier Apache sans pollution "/public"
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $baseDir = rtrim(dirname($scriptName), '/');

        return $protocol . '://' . $host . $baseDir . '/' . ltrim($url, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): Response
    {
        return (new Response())->redirect($url);
    }
}

if (!function_exists('redirectBack')) {
    function redirectBack(): Response
    {
        return (new Response())->redirectBack();
    }
}

if (!function_exists('encrypt')) {
    function encrypt($value): string
    {
        return (new Encrypter())->encrypt($value);
    }
}

if (!function_exists('decrypt')) {
    function decrypt(string $token)
    {
        return (new Encrypter())->decrypt($token);
    }
}

if (!function_exists('auth')) {
    function auth()
    {
        return \Desinova\Aero\Auth::class;
    }
}

if (!function_exists('mail_to')) {
    function mail_to(string $to, string $subject, string $htmlContent): bool
    {
        return (new \Desinova\Aero\Mailer())->send($to, $subject, $htmlContent);
    }
}

if (!function_exists('asset')) {
    /**
     * Point d'ancrage absolu vers le NOUVEAU dossier assets/ à la racine d'Aero
     */
    function asset(string $path): string
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') 
                    ? 'https' : 'http';
        
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $baseDir = rtrim(dirname($scriptName), '/');

        // On cible directement le nouveau dossier assets/ à la racine
        return $protocol . '://' . $host . $baseDir . '/assets/' . ltrim($path, '/');
    }
}

/**
 * Télécharge un fichier et le stocke dans assets/{folder}
 */
function upload(array $file, string $folder = 'uploads'): ?string 
{
    if (!isset($file['tmp_name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Le dossier cible physique passe de public/storage/ à assets/
    $storagePath = __DIR__ . '/../assets/' . $folder;

    if (!is_dir($storagePath)) {
        mkdir($storagePath, 0777, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('', true) . '.' . $extension;
    $destination = $storagePath . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    // Retourne l'URL relative à enregistrer en BDD (ex: assets/uploads/fichier.png)
    return 'assets/' . $folder . '/' . $filename;
}

/**
 * Génère un avatar vectoriel SVG unique stocké dans assets/avatars/
 */
function generate_avatar(string $prenom, int $size = 200): string
{
    $prenom = trim($prenom);
    $initiale = !empty($prenom) ? mb_strtoupper(mb_substr($prenom, 0, 1, 'UTF-8'), 'UTF-8') : '?';

    $hash = md5($prenom);
    $color1 = '#' . substr($hash, 0, 6);
    $color2 = '#' . substr($hash, 6, 6);

    $svg = '
    <svg width="'.$size.'" height="'.$size.'" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="'.$color1.'"/>
                <stop offset="100%" stop-color="'.$color2.'"/>
            </linearGradient>
        </defs>
        <rect width="200" height="200" fill="url(#grad)" rx="35"/>
        <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle"
            font-size="85" fill="#ffffff" font-family="Outfit, sans-serif" font-weight="600">
            '.$initiale.'
        </text>
    </svg>
    ';

    // Ciblage du nouveau dossier d'actifs
    $avatarDir = __DIR__ . '/../assets/avatars/';

    if (!file_exists($avatarDir)) {
        mkdir($avatarDir, 0777, true);
    }

    $nomFichier = 'avatar_' . uniqid() . '.svg';
    file_put_contents($avatarDir . $nomFichier, $svg);

    // Retourne le chemin relatif d'accès public
    return 'assets/avatars/' . $nomFichier;
}

function generateSecurePassword($length = 12) {
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()_+{}[]|:;<>,.?~';
    $all = $lowercase . $uppercase . $numbers . $symbols;
    
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    return str_shuffle($password);
}

/**
 * Envoie un message WhatsApp via l'API de Green-API (cURL)
 */
function sendWhatsAppMessage($numero, $message)
{
    try {
        $message = (string)($message ?? '');
        $numero = (string)($numero ?? '');

        if (empty($message)) {
            return ['success' => false, 'message' => "Le contenu du message est vide."];
        }

        $digits = preg_replace('/[^0-9]/', '', $numero);

        if (strlen($digits) === 10 && strpos($digits, '01') === 0) {
            $brut8 = substr($digits, 2);
        } elseif (strlen($digits) === 13 && strpos($digits, '22901') === 0) {
            $brut8 = substr($digits, 5);
        } elseif (strlen($digits) === 11 && strpos($digits, '229') === 0) {
            $brut8 = substr($digits, 3);
        } else {
            $brut8 = $digits;
        }

        $formatAncien  = '229' . $brut8;
        $formatNouveau = '22901' . $brut8;

        $idInstance = "7107644381";
        $apiTokenInstance = "65cb028ffd9c429f94183ce7a1f15af90e14adb61a90440cb4";
        $host = "https://api.green-api.com";

        $greenApiClient = new GreenApiClient($idInstance, $apiTokenInstance, $host);
        $responseAncien = $greenApiClient->sending->sendMessage($formatAncien . '@c.us', $message);

        if ($responseAncien && isset($responseAncien->data->idMessage)) {
            try {
                $greenApiClient->sending->sendMessage($formatNouveau . '@c.us', $message);
            } catch (\Exception $e) {}

            return [
                'success' => true,
                'message' => 'Message WhatsApp distribué avec succès aux passerelles Green-API.',
                'idMessage' => $responseAncien->data->idMessage
            ];
        }

        return ['success' => false, 'message' => "L'API Green-API n'a pas pu prendre en charge le message."];
    } catch (\Exception $e) {
        return ['success' => false, 'message' => "Exception technique Green-API : " . $e->getMessage()];
    }
}

if (!function_exists('session')) {
    function session() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return new class {
            public function get($key, $default = null) { return $_SESSION[$key] ?? $default; }
            public function put($key, $value) { $_SESSION[$key] = $value; }
            public function has($key) { return isset($_SESSION[$key]); }
            public function forget($key) { if (isset($_SESSION[$key])) unset($_SESSION[$key]); }
        };
    }
}

if (!function_exists('response_json')) {
    function response_json(array $data, int $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('is_active')) {
    function is_active(string $path, string $activeClass = 'active'): string
    {
        $currentPath = Application::getInstance()->request->getPath();
        return '/' . ltrim($currentPath, '/') === '/' . ltrim($path, '/') ? $activeClass : '';
    }
}

function getGreeting() {
    $heure = (int) date('H');
    if ($heure >= 0 && $heure < 4) return "🌙 Il fait nuit";
    if ($heure >= 4 && $heure < 12) return "☀️ Bonjour";
    if ($heure >= 12 && $heure < 16) return "☕ Bon après-midi";
    if ($heure >= 16 && $heure < 22) return "🌆 Bonsoir";
    return "🌙 Il est tard";
}