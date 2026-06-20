# ✈️ Aero Framework - Documentation Officielle

Aero est un micro-framework PHP hybride, ultra-léger et conçu pour être déployé en "Drop-and-Play" (sans configuration complexe de serveur sous XAMPP, Apache ou en production).

## 🏢 Architecture du Projet

```text
Dossier racine/
├── assets/          # Fichiers publics (CSS, JS, Images, Uploads)
├── cache/           # Fichiers de vues compilés pour booster les performances
├── config/          # Fichiers de configuration (Base de données, Routes, Middlewares)
├── core/            # Le moteur interne du framework (Application, Router, Relations)
├── includes/        # Le code de ton application (Contrôleurs, Modèles, Middlewares)
├── pages/           # Tes fichiers de vues HTML (Interface utilisateur)
├── vendor/          # Dépendances gérées par Composer
└── index.php        # Point d'entrée unique de l'application
```
## Pourquoi un fichier .htaccess ?
Par défaut, si vous tapez localhost/monprojet/pages/profil.php, le serveur tente d'ouvrir directement le fichier. Cela pose deux problèmes :

Sécurité : Vos fichiers internes sont exposés.

Flexibilité : Impossible de faire des URLs propres (comme /profil).

Le .htaccess force Apache à interdire l'accès aux dossiers sensibles et redirige discrètement n'importe quelle URL vers index.php.

## Le rôle de index.php
index.php est le chef d'orchestre. Il ne contient pas de logique métier (pas de HTML de page, pas de requêtes SQL). Son seul but est de :

Brancher l'Autoloader pour que PHP trouve vos classes tout seul.

Charger les fonctions d'aide globales (helpers.php).

Instancier le cœur du système (Application) pour lui dire de démarrer.

## ⚙️ Étape 2 : Autoloading et Configuration Isolation

Pour éviter d'écrire des `require_once` partout à chaque fois qu'on crée un nouveau contrôleur ou un nouveau modèle, Aero s'appuie sur le gestionnaire de dépendances **Composer** et la norme d'auto-chargement **PSR-4**.

### Pourquoi utiliser des Namespaces ?
Pensez aux namespaces (espaces de noms) comme à des dossiers virtuels pour vos classes PHP. Si vous avez une classe `Router` dans le cœur du framework et une classe `Router` externe, les namespaces évitent les collisions de noms.
- Tout ce qui touche au moteur interne est rangé sous : `Desinova\Aero\`
- Tout votre code applicatif (le dossier `includes/`) est rangé sous : `App\`

### L'esprit de la configuration hybride
Dans Aero, les fichiers situés dans `config/` ne font qu'une seule chose : **renvoyer un tableau de données (`return [...]`)**.
Le cœur du framework se chargera de lire ce tableau. Cela permet d'isoler complètement la logique du code des paramètres variables (comme les accès à la base de données locale ou de production). Si un projet Aero n'utilise pas de base de données, le moteur s'adapte sans planter.

## 🏎️ Étape 3 : Le Cœur du Réacteur (Application.php)

La classe `Application` implémente le patron de conception **Singleton** combiné à un rôle de **Conteneur d'exécution**. C'est elle qui instancie les composants systèmes (`Request`, `Router`), intercepte les anomalies et compile les gabarits graphiques.

### 1. Pourquoi utiliser un Singleton (`getInstance()`) ?
Dans une application web, il ne doit exister qu'une seule et unique configuration active à la fois. Le Singleton permet d'accéder au framework depuis n'importe quel fichier de votre code via l'appel statique :
```php
\Desinova\Aero\Application::getInstance();

## 🧭 Étape 4 : Le Routage Statique et Dynamique

Le `Router` d'Aero est un mécanisme d'aiguillage à deux niveaux : il gère les chemins fixes (ex: `/apropos`) et capture les variables à la volée (ex: `/citoyen/{id}`).

### 1. Comment fonctionne la capture dynamique (Regex Mapping) ?
Lorsqu'une route contient des accolades `{id}`, PHP ne peut pas l'associer directement à une chaîne de caractères fixe issue de l'URL courante. Aero effectue alors une traduction dynamique :
- Il remplace `{id}` par un masque regex `([^/]+)` (qui signifie : "tout caractère sauf un slash").
- Il confronte l'URL reçue à ce masque via `preg_match`.
- Si le masque concorde, les valeurs capturées sont extraites et transmises de manière sécurisée sous forme d'arguments à votre contrôleur.

### 2. Le concept de "Short-Circuit" (Court-circuit) par Middleware
Le système de routage intègre un pipeline de filtrage avant d'exécuter l'action finale d'un contrôleur. C'est l'implémentation des Middlewares :
Chaque middleware est instancié et exécuté l'un après l'autre. Si un filtre échoue (ex: un utilisateur non connecté tente d'accéder à son profil), le middleware renvoie immédiatement une réponse d'erreur ou une redirection. La boucle `foreach` s'interrompt net via un `return` anticipé : le contrôleur sous-jacent n'est jamais instancié, économisant de la mémoire et préservant la sécurité du site.

## 🌐 Étape 5 : L'Abstraction HTTP (Request & Response)

PHP fournit nativement des superglobales (`$_GET`, `$_POST`, `$_SERVER`). Cependant, les manipuler directement dans votre code métier rend l'application rigide et difficile à maintenir. Aero encapsule ces données dans deux objets dédiés.

### 1. L'importance du nettoyage d'URI sans dossier public
Puisque la nouvelle architecture d'Aero place l'index directement à la racine, le rôle de `Request::getPath()` s'est grandement simplifié. Le moteur calcule de manière dynamique le dossier dans lequel il est installé (grâce à `dirname($_SERVER['SCRIPT_NAME'])`). 
Si vous glissez le framework dans un sous-dossier comme `localhost/CiteNanNan/`, Aero détecte automatiquement que la racine applicative commence après `/CiteNanNan`. Cela supprime le besoin d'écrire des configurations complexes ou de nettoyer de force des dossiers virtuels `/public`.

### 2. Comment fonctionne le mécanisme du Pipeline JSON ?
Aujourd'hui, une application moderne communique autant via des formulaires classiques que par des requêtes asynchrones JavaScript (API via `fetch` ou `axios`).
Lorsqu'un script JS envoie des données, celles-ci n'apparaissent pas dans la superglobale `$_POST`. Elles sont envoyées dans le corps brut de la requête HTTP. Aero lit ce flux caché grâce à :
```php
file_get_contents('php://input');

## 🗄️ Étape 6 : La Connexion Hybride et le Lazy Loading

Le composant `Database` d'Aero résout un problème majeur des frameworks lourds : l'allocation inutile de ressources. 

### 1. Le concept de Lazy Loading (Chargement fainéant)
Traditionnellement, dès que le framework démarre, il initie une connexion avec le serveur de base de données. Si votre page d'accueil fait du simple rendu HTML sans toucher à SQL, cette connexion consomme de la mémoire serveur pour rien. 
Aero applique le **Lazy Loading** : la méthode `__construct` se contente d'enregistrer le tableau de configuration en mémoire (une opération qui prend moins d'une microseconde). C'est uniquement au moment où votre code appelle explicitement `$db->pdo()` pour faire une requête que la connexion s'ouvre. Si vous n'appelez jamais la base de données, la connexion ne s'établit jamais.

### 2. Pourquoi encapsuler PDO ?
Plutôt que de laisser PHP lever une erreur système brute `PDOException` (souvent agressive et révélant des informations sensibles sur vos ports ou identifiants), Aero intercepte l'erreur dans un bloc `try/catch` et la convertit en une exception standardisée :
```php
throw new \Exception("Erreur critique de base de données : " . $e->getMessage(), ...);


## 🧰 Étape 7 : Les Helpers Globaux et la Unification des Assets

Le fichier `helpers.php` contient des fonctions utilitaires écrites en dehors de toute classe. Elles utilisent l'instance globale de votre conteneur (`Application::getInstance()`) pour s'interfacer avec le framework.

### 1. Pourquoi avons-nous modifié l'extraction des chemins ?
Dans l'ancienne architecture type Laravel, n'importe quel lien vers un script ou un style CSS devait pointer vers le répertoire `/public/css/...`.
Avec la nouvelle structure d'Aero, **le dossier `/public` disparaît au profit du dossier racine `/assets`**.
- La fonction `asset('css/style.css')` calcule de façon dynamique la racine du projet (qu'il soit dans un sous-dossier ou un domaine) et injecte la cible `/assets/css/style.css`.
- Les fonctions d'écriture sur disque (`upload()` et `generate_avatar()`) n'utilisent plus l'arborescence `/public/storage/` mais écrivent directement dans `assets/uploads/` et `assets/avatars/`.

### 2. Persistance et adresses relatives en base de données
Lorsque vous enregistrez le chemin d'un avatar ou d'une image téléversée par un citoyen en base de données, **n'enregistrez jamais le chemin serveur absolu** (ex: `C:/xampp/htdocs/...`). Enregistrez la chaîne relative calculée par le helper : `assets/uploads/nom_de_fichier.png`. 
De cette manière, si vous déplacez votre projet de XAMPP vers un serveur Linux en production, tous vos fichiers restent lisibles instantanément par votre code HTML en utilisant simplement :
```html
<img src="<?= asset($utilisateur['avatar']) ?>" alt="Avatar">

## 🎮 Étape 8 : L'évolution de la classe Controller

Dans les architectures classiques, les contrôleurs de l'application héritent tous d'une classe `Controller` de base. Dans Aero, grâce à la mise en place du **Singleton** et des **Helpers globaux**, cet héritage est devenu optionnel mais reste une question de confort.

### La fin des variables globales (`global $app`)
L'utilisation de `global $app` brisait l'encapsulation et rendait le code dépendant de l'environnement global (si la variable changeait de nom dans l'index, tout plantait). Désormais, la classe `Controller` interroge directement le point d'accès statique `Application::getInstance()`. Vos contrôleurs applicatifs restent ainsi légers et hautement découplés.


## 🚪 Étape 9 : Le Single Point of Entry (index.php)

Le fichier `index.php` placé à la racine est le chef d'orchestre du démarrage du framework. C'est le concept de **Front Controller** utilisé par les frameworks industriels.

### 1. Le cycle d'allumage (Bootstrapping)
L'ordre des inclusions dans ce fichier est crucial et suit une logique séquentielle stricte :
1. **L'Autoloader (`vendor/autoload.php`) :** Il doit charger en premier pour que PHP connaisse l'emplacement de nos classes (`Application`, `Router`, etc.) avant qu'on ne tente de les appeler.
2. **Les Helpers (`core/helpers.php`) :** Chargés immédiatement après afin que toutes leurs fonctions globales soient utilisables dès la phase de configuration.
3. **La Configuration (`config/app.php`) :** Elle est lue et injectée dans l'instance de l'application pour paramétrer les accès aux bases de données ou aux APIs tierces.
4. **Le Registre de Routes (`routes/web.php`) :** Chargé juste avant l'exécution pour s'assurer que toutes les routes sont enregistrées dans la mémoire du `Router`.

### 2. L'absence de sorties directes (`echo`) avant le `run()`
Un bon fichier `index.php` ne doit jamais afficher de texte brut en dehors du cycle de rendu du framework. Faire un `echo` ou laisser un espace blanc avant l'initialisation de l'application peut corrompre l'envoi des en-têtes HTTP (headers), bloquant ainsi les redirections automatiques (`header('Location: ...')`) ou le paramétrage des cookies de session.

## 🛡️ Étape 11 : Le Contrat d'Interception (Middleware Interface)

L'architecture d'Aero repose sur le principe de la "sécurité par contrat". La `MiddlewareInterface` applique un concept fondamental de la programmation orientée objet : le **Polymorphisme**.

### Pourquoi une Interface est-elle indispensable ici ?
Le `Router` ne sait pas à l'avance ce que vos filtres vont faire (vérifier si un citoyen est connecté, bloquer une IP, valider un jeton API). Cependant, parce que chaque middleware implémente obligatoirement la `MiddlewareInterface`, le framework a la garantie absolue que la méthode `.execute()` existe, prend les mêmes types de paramètres et retourne le même format de réponse.

Cela permet d'ajouter à l'infini de nouvelles barrières de sécurité dans votre application sans jamais avoir à modifier une seule ligne du code interne d'Aero.