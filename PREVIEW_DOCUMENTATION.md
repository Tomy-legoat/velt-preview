# Documentation Module Preview Velt

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Utilisation CLI](#utilisation-cli)
6. [API HTTP](#api-http)
7. [Format des fichiers .velt](#format-des-fichiers-velt)
8. [Intégration Kernel](#intégration-kernel)
9. [Tests](#tests)
10. [Dépannage](#dépannage)

---

## Vue d'ensemble

Le module Preview permet la **prévisualisation mobile** des templates Velt en temps réel via QR code + JSON.

### Fonctionnalités principales

- ✅ Génération de session preview avec ID unique
- ✅ Création d'URL de preview accessibles via HTTP
- ✅ Génération d'image QR code (SVG)
- ✅ API REST endpoints pour récupérer le JSON
- ✅ Parser de fichiers `.velt` vers AST
- ✅ Transformation AST en JSON structuré
- ✅ Intégration complète avec le Kernel VeltPHP

### Flux complet

```
fichier .velt → VeltParser → AST → VeltView::toJson() → JSON → API Preview → Mobile
```

---

## Architecture

### Structure des modules

```
velt-preview/
├── composer.json              # Dépendances centralisées
├── vendor/                    # Toutes les dépendances (centralisé)
│
├── preview-contracts/          # Contrats partagés
│   ├── PreviewPage.php         # Modèle de page
│   ├── Contract/
│   │   ├── PageRepositoryInterface.php
│   │   ├── JsonRendererInterface.php
│   │   └── PreviewSchema.php  # Schéma JSON versionné
│   └── Error/
│       └── PreviewErrorType.php
│
├── preview-session-store/      # Stockage des sessions
│   ├── PreviewSession.php      # Modèle de session
│   ├── PreviewSessionStore.php # Gestionnaire de stockage
│   └── Exceptions/
│       └── PreviewSessionNotFoundException.php
│
├── preview-endpoints/          # Endpoints HTTP
│   ├── src/Http/
│   │   ├── PreviewController.php
│   │   ├── PreviewErrorResponse.php
│   │   └── Request.php
│   ├── src/Renderer/
│   │   └── ArrayJsonRenderer.php
│   ├── src/Repository/
│   │   └── ArrayPageRepository.php
│   └── public/index.php        # Point d'entrée HTTP
│
├── preview-qr-cli/             # CLI pour QR code
│   ├── bin/velt                # Exécutable CLI
│   ├── src/
│   │   ├── PreviewUrlGenerator.php
│   │   ├── QRGenerator.php     # Génération QR code
│   │   └── Repository/
│   │       └── ArrayViewRegistry.php
│   └── storage/qrcodes/        # Stockage des QR générés
│
├── velt-ast/                   # Structure AST
│   ├── NodeInterface.php       # Interface des nœuds
│   ├── AST.php                 # Racine de l'AST
│   └── Nodes/
│       ├── VStack.php          # Conteneur vertical
│       ├── HStack.php          # Conteneur horizontal
│       ├── Text.php            # Texte
│       ├── Button.php          # Bouton
│       ├── Input.php           # Champ de saisie
│       └── Container.php       # Conteneur générique
│
├── velt-parser/                # Parser .velt
│   └── VeltParser.php         # Parser principal
│
├── velt-view/                  # Chargement et rendu
│   ├── VeltView.php            # Classe principale
│   └── VeltPageRepository.php  # Repository de pages
│
└── templates/                  # Fichiers .velt
    ├── auth/login.velt
    └── home/dashboard.velt
```

### Gestion des dépendances

Les dépendances sont **centralisées** au niveau du dossier `velt-preview` :

- **Un seul `composer.json`** à la racine
- **Autoloading PSR-4** configuré pour tous les modules
- **Un seul dossier `vendor/`** partagé
- **Installation simplifiée** : `composer install` depuis la racine

---

## Installation

### Prérequis

- PHP >= 8.1
- Composer
- Extensions PHP : json, fileinfo

### Installation des dépendances

```bash
# Installer toutes les dépendances (centralisé)
cd velt-preview
composer install
```

### Configuration des chemins

Créer les répertoires nécessaires :

```bash
mkdir -p preview-qr-cli/storage/qrcodes
mkdir -p preview-endpoints/storage
mkdir -p templates/auth
mkdir -p templates/home
```

---

## Configuration

### Configuration du CLI

Le fichier `bin/velt` utilise les chemins suivants par défaut :

```php
$templatesPath = dirname(__DIR__, 2) . '/templates';
$storagePath = __DIR__ . '/../storage';
$baseUrl = 'http://127.0.0.1:8000';
```

### Configuration de l'API

Le fichier `preview-endpoints/public/index.php` configure :

```php
$templatesPath = __DIR__ . '/../../templates';
$repository = VeltPageRepositoryFactory::create($templatesPath);
```

### Variables d'environnement (optionnel)

```bash
# .env
PREVIEW_BASE_URL=http://192.168.1.X:8000
PREVIEW_SESSION_TTL=3600
```

---

## Utilisation CLI

### Créer une session preview

```bash
cd preview-qr-cli
php bin/velt preview auth.login
```

**Résultat :**

```
Preview session created:
ID: 0d808a45e1f3
URL: http://127.0.0.1:8000/api/preview/0d808a45e1f3
QR payload: http://127.0.0.1:8000/api/preview/0d808a45e1f3
QR image: storage/qrcodes/0d808a45e1f3.svg
Scan this QR to preview on mobile
```

### Commandes disponibles

```bash
php bin/velt preview <view>    # Créer une session preview
```

### Erreurs courantes

**Template non trouvé :**

```
Error: Template not found: templates/auth/login.velt
Available templates:
  - auth.login
  - home.dashboard
```

---

## API HTTP

### Démarrer le serveur

```bash
cd preview-endpoints
php -S 0.0.0.0:8000 -t public
```

### Endpoints disponibles

#### GET /api/preview/{id}

Récupère le JSON de preview pour une session.

**Requête :**

```http
GET /api/preview/0d808a45e1f3 HTTP/1.1
Host: localhost:8000
```

**Réponse succès (200) :**

```json
{
  "schemaVersion": "1.0",
  "screen": "auth.login",
  "components": [
    {
      "type": "VStack",
      "class": "flex-1 p-4",
      "props": {
        "class": "flex-1 p-4"
      },
      "children": [
        {
          "type": "Text",
          "value": "Se connecter",
          "class": "text-2xl font-bold mb-4",
          "props": {
            "value": "Se connecter",
            "class": "text-2xl font-bold mb-4"
          }
        },
        {
          "type": "Input",
          "name": "email",
          "label": "Email",
          "inputType": "email",
          "class": "mb-4",
          "props": {
            "name": "email",
            "label": "Email",
            "type": "email",
            "class": "mb-4"
          }
        },
        {
          "type": "Button",
          "text": "Connexion",
          "class": "bg-blue-500 text-white",
          "props": {
            "text": "Connexion",
            "class": "bg-blue-500 text-white"
          }
        }
      ]
    }
  ],
  "meta": {
    "source": "auth.login"
  }
}
```

**Réponse erreur (404) :**

```json
{
  "error": {
    "code": "SESSION_NOT_FOUND",
    "message": "Preview session not found"
  }
}
```

#### GET /api/session/{id}

Récupère les informations de session.

**Requête :**

```http
GET /api/session/0d808a45e1f3 HTTP/1.1
Host: localhost:8000
```

**Réponse succès (200) :**

```json
{
  "id": "0d808a45e1f3",
  "view": "auth.login",
  "url": "http://127.0.0.1:8000/api/preview/0d808a45e1f3",
  "createdAt": "2026-06-17T03:59:00+00:00",
  "expiresAt": null
}
```

**Réponse erreur (404) :**

```json
{
  "error": {
    "code": "SESSION_NOT_FOUND",
    "message": "Preview session not found"
  }
}
```

**Réponse erreur (410) - Session expirée :**

```json
{
  "error": {
    "code": "SESSION_EXPIRED",
    "message": "Preview session expired"
  }
}
```

---

## Format des fichiers .velt

### Syntaxe de base

Les fichiers `.velt` utilisent une syntaxe simple basée sur l'indentation :

```
Composant prop1="valeur1" prop2="valeur2"
  ComposantEnfant prop="valeur"
  AutreComposant prop="valeur"
```

### Composants disponibles

#### VStack - Conteneur vertical

```
VStack class="flex-1 p-4"
  Text value="Titre"
  Button text="Action"
```

#### HStack - Conteneur horizontal

```
HStack class="flex-row"
  Text value="Gauche"
  Text value="Droite"
```

#### Text - Texte

```
Text value="Mon texte" class="text-lg font-bold"
```

#### Button - Bouton

```
Button text="Cliquez-moi" class="bg-blue-500"
```

#### Input - Champ de saisie

```
Input name="email" label="Email" type="email" class="mb-4"
Input name="password" label="Mot de passe" type="password"
```

#### Container - Conteneur générique

```
Container class="p-4"
  Text value="Contenu"
```

### Exemple complet

**Fichier :** `templates/auth/login.velt`

```velt
VStack class="flex-1 p-4"
  Text value="Se connecter" class="text-2xl font-bold mb-4"
  Input name="email" label="Email" type="email" class="mb-4"
  Input name="password" label="Mot de passe" type="password" class="mb-4"
  Button text="Connexion" class="bg-blue-500 text-white"
```

### Règles

- L'indentation définit la hiérarchie (2 espaces recommandés)
- Les props sont au format `key="value"`
- Les lignes vides sont ignorées
- Les commentaires commencent par `//`

---

## Intégration Kernel

### Enregistrement du ServiceProvider

Dans le bootstrap de votre application :

```php
use Velt\Kernel\Application;
use Velt\Kernel\PreviewServiceProvider;

$app = new Application(__DIR__);
$app->registerProvider(new PreviewServiceProvider($app));
$app->boot();
```

### Utilisation via le contrat

```php
use Velt\Kernel\Contracts\PreviewServiceInterface;

class MyController
{
    public function __construct(
        private PreviewServiceInterface $preview
    ) {}

    public function createPreview()
    {
        $session = $this->preview->createSession('auth.login');
        
        return [
            'id' => $session['id'],
            'url' => $session['url'],
            'qr_payload' => $session['qrPayload']
        ];
    }

    public function getPreview(string $sessionId)
    {
        return $this->preview->getPreviewData($sessionId);
    }
}
```

### Services disponibles dans le container

| Service | Description |
|---------|-------------|
| `preview.templates_path` | Chemin vers les templates .velt |
| `preview.storage_path` | Chemin vers le stockage des sessions |
| `preview.base_url` | URL de base pour les endpoints |
| `preview.parser` | Instance de VeltParser |
| `preview.page_repository` | Instance de VeltPageRepository |
| `preview.session_store` | Instance de PreviewSessionStore |
| `preview.controller` | Instance de PreviewController |
| `preview.url_generator` | Instance de PreviewUrlGenerator |
| `preview.qr_generator` | Instance de QRGenerator |
| `PreviewServiceInterface` | Service Preview du kernel |

---

## Tests

### Test du parser

```bash
cd velt-preview
php -r "
require 'velt-parser/src/VeltParser.php';
require 'velt-ast/src/AST.php';

\$parser = new VeltParser\VeltParser();
\$content = file_get_contents('templates/auth/login.velt');
\$ast = \$parser->parse(\$content, 'auth.login');
echo json_encode(\$ast->toArray(), JSON_PRETTY_PRINT);
"
```

### Test de l'API

```bash
# Démarrer le serveur
cd preview-endpoints
php -S 0.0.0.0:8000 -t public

# Dans un autre terminal
curl http://localhost:8000/api/preview/{session_id}
```

### Test du CLI

```bash
cd preview-qr-cli
php bin/velt preview auth.login
```

### Test d'intégration

```php
use Velt\Kernel\Contracts\PreviewServiceInterface;

test('preview service creates session', function () {
    $preview = app(PreviewServiceInterface::class);
    $session = $preview->createSession('auth.login');
    
    expect($session)->toHaveKey('id');
    expect($session)->toHaveKey('url');
    
    $data = $preview->getPreviewData($session['id']);
    expect($data)->toHaveKey('screen');
    expect($data)->toHaveKey('components');
});
```

---

## Dépannage

### Le QR code ne fonctionne pas sur mobile

**Problème :** L'URL contient `127.0.0.1` qui n'est pas accessible depuis le mobile.

**Solution :**

1. Trouver ton IP locale :
```bash
# Windows
ipconfig

# Linux/Mac
ifconfig | grep inet
```

2. Modifier l'URL dans le CLI ou la configuration :
```bash
# Dans bin/velt ou config
$baseUrl = 'http://192.168.1.X:8000';
```

3. Démarrer le serveur sur toutes les interfaces :
```bash
php -S 0.0.0.0:8000 -t public
```

### Template non trouvé

**Erreur :**
```
Error: Template not found: templates/auth/login.velt
```

**Solution :**

1. Vérifier que le fichier existe :
```bash
ls templates/auth/login.velt
```

2. Vérifier le nom de la vue (utilise `.` comme séparateur) :
```bash
php bin/velt preview auth.login  # Correct
php bin/velt preview auth/login  # Incorrect
```

### Session expirée

**Erreur :**
```json
{
  "error": {
    "code": "SESSION_EXPIRED",
    "message": "Preview session expired"
  }
}
```

**Solution :**

Créer une nouvelle session ou augmenter le TTL :
```php
$session = $store->create('auth.login', $baseUrl, 7200); // 2 heures
```

### Erreur de parsing

**Erreur :**
```
RuntimeException: Unable to encode preview JSON
```

**Solution :**

1. Vérifier la syntaxe du fichier .velt
2. S'assurer que l'indentation est cohérente
3. Vérifier que toutes les props sont correctement formatées

---

## Schéma JSON

### Version du schéma

```json
{
  "schemaVersion": "1.0"
}
```

### Structure complète

```json
{
  "schemaVersion": "1.0",
  "screen": "nom_de_la_vue",
  "components": [
    {
      "type": "TypeComposant",
      "class": "classes CSS",
      "props": {
        "prop1": "valeur1",
        "prop2": "valeur2"
      },
      "children": [
        // Composants enfants
      ]
    }
  ],
  "meta": {
    "source": "nom_de_la_vue",
    "timestamp": "ISO-8601"
  }
}
```

### Types de composants

| Type | Description | Props spécifiques |
|------|-------------|------------------|
| `VStack` | Conteneur vertical | `class` |
| `HStack` | Conteneur horizontal | `class` |
| `Text` | Texte | `value`, `class` |
| `Button` | Bouton | `text`, `class` |
| `Input` | Champ de saisie | `name`, `label`, `type`, `class` |
| `Container` | Conteneur générique | `class` |

---

## Performance

### Optimisations

- Les sessions sont stockées dans un fichier JSON (MVP)
- Le parser utilise une tokenisation simple
- Le QR code est généré en SVG (léger)
- L'AST est mis en cache dans VeltView

### Limitations MVP

- Pas de WebSocket / Hot-Reload
- Pas de base de données (fichier JSON)
- Pas de cache avancé
- Pas de compression des réponses

---

## Sécurité

### Considérations

- Les sessions ont un TTL configurable
- Les IDs de session sont générés aléatoirement (12 caractères hex)
- Pas d'authentification requise pour le MVP
- Les templates sont lus depuis le système de fichiers

### Recommandations pour la production

- Ajouter l'authentification sur les endpoints
- Utiliser HTTPS pour les URLs de preview
- Implémenter un rate limiting
- Valider les noms de vues
- Nettoyer régulièrement les sessions expirées

---

## Roadmap

### Version actuelle (MVP)

- ✅ Génération de session
- ✅ API REST basique
- ✅ Parser .velt
- ✅ Génération QR code
- ✅ Intégration Kernel

### Futures améliorations

- ⏳ WebSocket / Hot-Reload
- ⏳ Base de données pour les sessions
- ⏳ Cache avancé
- ⏳ Compression des réponses
- ⏳ Authentification
- ⏳ Rate limiting
- ⏳ Support des composants avancés

---

## Support

### Documentation additionnelle

- [README du module Preview](README.md)
- [Intégration Kernel](../veltphp-kernel/packages/kernel/docs/PREVIEW_INTEGRATION.md)
- [Issues du projet](issues/)

### Contribution

Pour contribuer au module Preview :

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commit vos changements
4. Push vers la branche
5. Ouvrir une Pull Request

---

## Licence

Ce module fait partie du projet VeltPHP. Voir la licence principale du projet pour plus d'informations.
