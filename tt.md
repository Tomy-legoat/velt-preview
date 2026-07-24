# README Explicatif - Module Velt Preview

## Table des matières

1. [Architecture Globale](#architecture-globale)
2. [Vue d'ensemble des modules](#vue-densemble-des-modules)
3. [Explication détaillée de chaque module](#explication-détaillée-de-chaque-module)
4. [Flux de données complet](#flux-de-données-complet)
5. [Intégration dans le framework](#intégration-dans-le-framework)

---

## Architecture Globale

### Diagramme d'architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Module Velt Preview                           │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                 Couche CLI (Interface Utilisateur)         │ │
│  │  ┌──────────────────────────────────────────────────────┐  │ │
│  │  │  preview-qr-cli/bin/velt                            │  │ │
│  │  │  - Crée des sessions preview                        │  │ │
│  │  │  - Génère des QR codes                              │  │ │
│  │  │  - Affiche les URLs                                 │  │ │
│  │  └──────────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │               Couche Logique (Business Logic)              │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │ │
│  │  │preview-session│  │ velt-parser  │  │  velt-view   │    │ │
│  │  │    -store     │  │              │  │              │    │ │
│  │  │  Gestion      │  │  Parser .velt│  │  Chargement  │    │ │
│  │  │  sessions     │  │  → AST       │  │  + Rendu     │    │ │
│  │  └──────────────┘  └──────────────┘  └──────────────┘    │ │
│  │         │                  │                  │           │ │
│  │         └──────────────────┴──────────────────┘           │ │
│  │                            │                               │ │
│  │                            ▼                               │ │
│  │              ┌──────────────────────┐                    │ │
│  │              │     velt-ast         │                    │ │
│  │              │  Structure AST       │                    │ │
│  │              │  (VStack, Text...)   │                    │ │
│  │              └──────────────────────┘                    │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │            Couche Contrats (Interfaces)                    │ │
│  │  ┌──────────────────────────────────────────────────────┐  │ │
│  │  │           preview-contracts                          │  │ │
│  │  │  - PageRepositoryInterface                           │  │ │
│  │  │  - JsonRendererInterface                             │  │ │
│  │  │  - PreviewPage (modèle)                              │  │ │
│  │  │  - PreviewSchema (schéma JSON)                       │  │ │
│  │  └──────────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │           Couche API (Endpoints HTTP)                       │ │
│  │  ┌──────────────────────────────────────────────────────┐  │ │
│  │  │         preview-endpoints                             │  │ │
│  │  │  - PreviewController                                 │  │ │
│  │  │  - GET /api/preview/{id}                             │  │ │
│  │  │  - GET /api/session/{id}                             │  │ │
│  │  └──────────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │              Couche Stockage (Persistence)                  │ │
│  │  ┌──────────────────────────────────────────────────────┐  │ │
│  │  │  preview-session-store/storage/                      │  │ │
│  │  │  preview_sessions.json (fichier JSON)                │  │ │
│  │  └──────────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │              Couche Templates (Fichiers .velt)            │ │
│  │  ┌──────────────────────────────────────────────────────┐  │ │
│  │  │  templates/auth/login.velt                           │  │ │
│  │  │  templates/home/dashboard.velt                       │  │ │
│  │  └──────────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### Flux de données

```
1. CLI → Crée session → PreviewSessionStore
2. PreviewSessionStore → Stocke dans preview_sessions.json
3. PreviewUrlGenerator → Génère URL + QR payload
4. QRGenerator → Génère image QR (SVG)
5. Mobile scanne QR → Accède URL
6. PreviewController → Récupère session
7. PageRepository → Charge template .velt
8. VeltParser → Parse .velt → AST
9. VeltView → Transforme AST → JSON
10. PreviewController → Retourne JSON au mobile
```

---

## Vue d'ensemble des modules

### 1. preview-contracts
**Rôle :** Définit les interfaces et contrats partagés entre tous les modules.

**Fichiers :**
- `src/Contracts/PageRepositoryInterface.php` - Interface pour charger les pages
- `src/Contracts/JsonRendererInterface.php` - Interface pour rendre en JSON
- `src/PreviewPage.php` - Modèle de page
- `src/Contract/PreviewSchema.php` - Schéma JSON versionné
- `src/Error/PreviewErrorType.php` - Types d'erreurs

**Importance :** Garantit la cohérence entre les modules via des contrats stricts.

### 2. preview-session-store
**Rôle :** Gère le stockage et la récupération des sessions preview.

**Fichiers :**
- `src/PreviewSessionStore.php` - Gestionnaire de stockage
- `src/PreviewSession.php` - Modèle de session
- `src/Exceptions/PreviewSessionNotFoundException.php` - Exception session non trouvée

**Importance :** Persistance des sessions avec support TTL (Time To Live).

### 3. preview-endpoints
**Rôle :** Expose les endpoints HTTP pour l'API Preview.

**Fichiers :**
- `src/Http/PreviewController.php` - Contrôleur principal
- `src/Http/Request.php` - Requête HTTP
- `src/Http/Response.php` - Réponse HTTP
- `src/Http/PreviewErrorResponse.php` - Gestion des erreurs
- `src/Renderer/ArrayJsonRenderer.php` - Rendu JSON
- `src/Repository/ArrayPageRepository.php` - Repository de pages
- `public/index.php` - Point d'entrée HTTP

**Importance :** Interface HTTP pour les clients mobiles.

### 4. preview-qr-cli
**Rôle :** CLI pour créer des sessions et générer des QR codes.

**Fichiers :**
- `bin/velt` - Exécutable CLI
- `src/PreviewUrlGenerator.php` - Générateur d'URL
- `src/QRGenerator.php` - Générateur de QR code
- `src/Repository/ArrayViewRegistry.php` - Registry des vues
- `src/Exception/UnknownViewException.php` - Exception vue inconnue

**Importance :** Interface en ligne de commande pour les développeurs.

### 5. velt-ast
**Rôle :** Définit la structure AST (Abstract Syntax Tree) pour les composants UI.

**Fichiers :**
- `src/NodeInterface.php` - Interface des nœuds
- `src/AST.php` - Racine de l'AST
- `src/Nodes/VStack.php` - Conteneur vertical
- `src/Nodes/HStack.php` - Conteneur horizontal
- `src/Nodes/Text.php` - Texte
- `src/Nodes/Button.php` - Bouton
- `src/Nodes/Input.php` - Champ de saisie
- `src/Nodes/Container.php` - Conteneur générique

**Importance :** Structure de données pour représenter les composants UI.

### 6. velt-parser
**Rôle :** Parse les fichiers .velt en AST.

**Fichiers :**
- `src/VeltParser.php` - Parser principal

**Importance :** Transformation du format texte .velt en structure AST.

### 7. velt-view
**Rôle :** Charge les templates et transforme l'AST en JSON.

**Fichiers :**
- `src/VeltView.php` - Classe principale
- `src/VeltPageRepository.php` - Repository de pages

**Importance :** Pont entre les fichiers .velt et le JSON final.

### 8. preview-flow-e2e
**Rôle :** Tests end-to-end du flux preview.

**Fichiers :**
- `src/PreviewFlowRunner.php` - Runner de tests E2E

**Importance :** Validation du flux complet.

### 9. preview-json-contract
**Rôle :** Validation du contrat JSON.

**Fichiers :**
- `src/Renderer/ContractJsonRenderer.php` - Rendu selon contrat
- `src/Error/PreviewErrorFactory.php` - Factory d'erreurs

**Importance :** Garantit la conformité du JSON.

---

## Explication détaillée de chaque module

### Module 1: preview-session-store

#### Fichier: PreviewSessionStore.php

**Rôle :** Gestionnaire de stockage des sessions preview dans un fichier JSON.

**Explication ligne par ligne :**

```php
<?php
namespace PreviewSessionStore;

use PreviewSessionStore\Exceptions\PreviewSessionNotFoundException;

class PreviewSessionStore
{
    private string $filePath;  // Chemin du fichier de stockage

    // Ligne 10-23: Constructeur
    public function __construct(string $directory, string $filename = 'preview_sessions.json')
    {
        // Ligne 12-16: Crée le répertoire s'il n'existe pas
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException("Unable to create directory: $directory");
            }
        }

        // Ligne 18: Construit le chemin complet du fichier
        $this->filePath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        // Ligne 20-22: Crée le fichier s'il n'existe pas avec un objet vide
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode(new \stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    // Ligne 29-42: Lecture des données depuis le fichier
    private function readData(): array
    {
        $contents = @file_get_contents($this->filePath);  // Lit le fichier
        if ($contents === false || trim($contents) === '') {
            return [];  // Retourne tableau vide si fichier vide
        }

        $decoded = json_decode($contents, true);  // Décode le JSON
        if (!is_array($decoded)) {
            return [];  // Retourne tableau vide si JSON invalide
        }

        return $decoded;  // Retourne les données
    }

    // Ligne 48-61: Écriture des données dans le fichier avec lock
    private function writeData(array $data): void
    {
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new \RuntimeException('Unable to JSON encode preview sessions');
        }

        $tmp = $this->filePath . '.tmp';  // Fichier temporaire
        $bytes = file_put_contents($tmp, $encoded, LOCK_EX);  // Écrit avec lock exclusif
        if ($bytes === false) {
            throw new \RuntimeException('Unable to write preview sessions file');
        }
        rename($tmp, $this->filePath);  // Renomme le fichier temporaire (opération atomique)
    }

    // Ligne 63-83: Création d'une nouvelle session
    public function create(string $view, string $baseUrl = '', ?int $ttlSeconds = null): PreviewSession
    {
        $id = bin2hex(random_bytes(6));  // Génère un ID unique (12 caractères hex)
        $createdAt = (new \DateTimeImmutable())->format(DATE_ATOM);  // Date de création ISO 8601
        $expiresAt = null;

        // Ligne 69-73: Calcule la date d'expiration si TTL spécifié
        if ($ttlSeconds !== null && $ttlSeconds > 0) {
            $expiresAt = (new \DateTimeImmutable())
                ->add(new \DateInterval('PT' . $ttlSeconds . 'S'))
                ->format(DATE_ATOM);
        }
        
        // Ligne 74: Construit l'URL de preview
        $url = $baseUrl === '' ? '/api/preview/' . $id : rtrim($baseUrl, '/') . '/api/preview/' . $id;

        $session = new PreviewSession($id, $view, $url, $createdAt, $expiresAt);  // Crée l'objet session

        $data = $this->readData();  // Lit les données existantes
        $data[$id] = $session->toArray();  // Ajoute la nouvelle session
        $this->writeData($data);  // Sauvegarde

        return $session;  // Retourne la session créée
    }

    // Ligne 85-93: Récupération d'une session par ID
    public function get(string $id): ?PreviewSession
    {
        $data = $this->readData();  // Lit les données
        if (!isset($data[$id])) {
            return null;  // Retourne null si non trouvé
        }

        return PreviewSession::fromArray($data[$id]);  // Reconstruit l'objet session
    }

    // Ligne 95-114: Nettoyage des sessions expirées
    public function purgeExpired(): int
    {
        $data = $this->readData();
        $now = new \DateTimeImmutable();
        $removed = 0;

        // Ligne 101-107: Parcourt toutes les sessions
        foreach ($data as $id => $item) {
            $session = PreviewSession::fromArray($item);
            if ($session->isExpired($now)) {  // Vérifie l'expiration
                unset($data[$id]);  // Supprime la session expirée
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->writeData($data);  // Sauvegarde si des sessions supprimées
        }

        return $removed;  // Retourne le nombre de sessions supprimées
    }

    // Ligne 116-123: Récupération avec exception si non trouvé
    public function getOrFail(string $id): PreviewSession
    {
        $s = $this->get($id);
        if ($s === null) {
            throw new PreviewSessionNotFoundException("Preview session not found: $id");
        }
        return $s;
    }

    // Ligne 125-135: Suppression d'une session
    public function delete(string $id): bool
    {
        $data = $this->readData();
        if (!isset($data[$id])) {
            return false;  // Retourne false si non trouvé
        }

        unset($data[$id]);  // Supprime la session
        $this->writeData($data);  // Sauvegarde
        return true;
    }

    // Ligne 138-146: Récupération de toutes les sessions
    public function all(): array
    {
        $data = $this->readData();
        $out = [];
        foreach ($data as $item) {
            $out[] = PreviewSession::fromArray($item);  // Reconstruit tous les objets
        }
        return $out;
    }
}
```

**Rôle dans le module :** Persistance des sessions preview avec gestion TTL.

**Rôle dans le framework :** Service de stockage utilisé par tous les autres modules.

---

### Module 2: preview-contracts

#### Fichier: PageRepositoryInterface.php

**Rôle :** Interface pour charger les pages depuis différentes sources.

**Explication ligne par ligne :**

```php
<?php

namespace PreviewContracts\Contracts;

use PreviewContracts\PreviewPage;

interface PageRepositoryInterface
{
    // Ligne 9: Méthode pour trouver une page par son nom de vue
    public function findByView(string $view): ?\PreviewContracts\PreviewPage;
}
```

**Rôle dans le module :** Définit le contrat que tous les repositories doivent implémenter.

**Rôle dans le framework :** Garantit l'interchangeabilité des repositories.

---

### Module 3: preview-endpoints

#### Fichier: PreviewController.php

**Rôle :** Contrôleur HTTP pour les endpoints de preview.

**Explication ligne par ligne :**

```php
<?php

namespace PreviewEndpoints\Http;

use PreviewContracts\Contracts\JsonRendererInterface;
use PreviewContracts\Contracts\PageRepositoryInterface;
use PreviewContracts\PreviewPage;
use PreviewSessionStore\PreviewSessionStore;

class PreviewController
{
    // Ligne 12-17: Injection de dépendances
    public function __construct(
        private PreviewSessionStore $sessionStore,  // Stockage des sessions
        private PageRepositoryInterface $pageRepository,  // Repository des pages
        private JsonRendererInterface $renderer  // Rendu JSON
    ) {
    }

    // Ligne 19-31: Endpoint GET /api/session/{id}
    public function session(string $id): Response
    {
        $session = $this->sessionStore->get($id);  // Récupère la session
        if ($session === null) {
            return Response::json(PreviewErrorResponse::sessionNotFound()->toArray(), 404);
            // Retourne 404 si session non trouvée
        }

        if ($session->isExpired()) {
            return Response::json(PreviewErrorResponse::sessionExpired()->toArray(), 410);
            // Retourne 410 Gone si session expirée
        }

        return Response::json($session->toArray(), 200);  // Retourne les infos de session
    }

    // Ligne 33-50: Endpoint GET /api/preview/{id}
    public function preview(string $id): Response
    {
        $session = $this->sessionStore->get($id);  // Récupère la session
        if ($session === null) {
            return Response::json(PreviewErrorResponse::sessionNotFound()->toArray(), 404);
        }

        if ($session->isExpired()) {
            return Response::json(PreviewErrorResponse::sessionExpired()->toArray(), 410);
        }

        $page = $this->pageRepository->findByView($session->view);  // Charge la page
        if ($page === null) {
            return Response::json(PreviewErrorResponse::pageNotFound()->toArray(), 404);
            // Retourne 404 si page non trouvée
        }

        return new Response(200, $this->renderer->render($page), ['Content-Type' => 'application/json']);
        // Retourne le JSON de la page
    }
}
```

**Rôle dans le module :** Interface HTTP pour les clients mobiles.

**Rôle dans le framework :** Point d'entrée API pour le système de preview.

---

### Module 4: preview-qr-cli

#### Fichier: PreviewUrlGenerator.php

**Rôle :** Générateur d'URL de preview pour le CLI.

**Explication ligne par ligne :**

```php
<?php

namespace PreviewQrCli;

use PreviewQrCli\Contracts\ViewRegistryInterface;
use PreviewQrCli\Exception\UnknownViewException;
use PreviewSessionStore\PreviewSessionStore;

class PreviewUrlGenerator
{
    // Ligne 11-16: Injection de dépendances
    public function __construct(
        private PreviewSessionStore $sessionStore,  // Stockage des sessions
        private ViewRegistryInterface $viewRegistry,  // Registry des vues disponibles
        private string $baseUrl = 'http://127.0.0.1:8000'  // URL de base par défaut
    ) {
    }

    // Ligne 21-36: Création d'une session pour une vue
    public function createForView(string $view): array
    {
        if (!$this->viewRegistry->exists($view)) {
            throw new UnknownViewException('Unknown view: ' . $view);
            // Lève une exception si la vue n'existe pas
        }

        $session = $this->sessionStore->create($view, $this->baseUrl);
        // Crée une nouvelle session

        return [
            'id' => $session->id,
            'url' => $session->url,
            'qrPayload' => $session->url,  // L'URL sert de payload QR
            'view' => $session->view,
            'createdAt' => $session->createdAt,
        ];
    }
}
```

**Rôle dans le module :** Génération d'URL et QR payload pour le CLI.

**Rôle dans le framework :** Interface CLI pour créer des sessions preview.

---

### Module 5: velt-ast

#### Fichier: AST.php

**Rôle :** Racine de l'AST (Abstract Syntax Tree) représentant une page UI.

**Explication ligne par ligne :**

```php
<?php
namespace VeltAst;

use VeltAst\Nodes\VStack;
use VeltAst\Nodes\HStack;
use VeltAst\Nodes\Text;
use VeltAst\Nodes\Button;
use VeltAst\Nodes\Input;
use VeltAst\Nodes\Container;

class AST
{
    // Ligne 14-18: Constructeur
    public function __construct(
        public string $view,  // Nom de la vue
        public NodeInterface $root,  // Nœud racine de l'AST
        public array $meta = []  // Métadonnées optionnelles
    ) {}

    // Ligne 20-27: Conversion en tableau
    public function toArray(): array
    {
        return [
            'screen' => $this->view,
            'components' => [$this->root->toArray()],  // Tableau de composants
            'meta' => $this->meta
        ];
    }

    // Ligne 29-38: Reconstruction depuis un tableau
    public static function fromArray(array $data): self
    {
        $root = self::parseNode($data['components'][0] ?? []);  // Parse le nœud racine
        
        return new self(
            $data['screen'] ?? '',
            $root,
            $data['meta'] ?? []
        );
    }

    // Ligne 40-78: Parse un nœud depuis un tableau
    private static function parseNode(array $node): NodeInterface
    {
        $type = $node['type'] ?? '';  // Type du composant
        
        return match($type) {
            'VStack' => new VStack(
                array_map(fn($child) => self::parseNode($child), $node['children'] ?? []),
                $node['class'] ?? '',
                $node['props'] ?? []
            ),
            'HStack' => new HStack(
                array_map(fn($child) => self::parseNode($child), $node['children'] ?? []),
                $node['class'] ?? '',
                $node['props'] ?? []
            ),
            'Text' => new Text(
                $node['value'] ?? '',
                $node['class'] ?? '',
                $node['props'] ?? []
            ),
            'Button' => new Button(
                $node['text'] ?? '',
                $node['class'] ?? '',
                $node['props'] ?? []
            ),
            'Input' => new Input(
                $node['name'] ?? '',
                $node['label'] ?? '',
                $node['inputType'] ?? $node['type'] ?? 'text',
                $node['class'] ?? '',
                $node['props'] ?? []
            ),
            'Container' => new Container(
                array_map(fn($child) => self::parseNode($child), $node['children'] ?? []),
                $node['class'] ?? '',
                $node['props'] ?? []
            ),
            default => new VStack()  // Fallback sur VStack
        };
    }
}
```

**Rôle dans le module :** Structure de données pour représenter les composants UI.

**Rôle dans le framework :** Format intermédiaire entre .velt et JSON.

---

### Module 6: velt-parser

#### Fichier: VeltParser.php

**Rôle :** Parser les fichiers .velt en AST.

**Explication ligne par ligne :**

```php
<?php

namespace VeltParser;

use VeltAst\AST;
use VeltAst\Nodes\VStack;
use VeltAst\Nodes\HStack;
use VeltAst\Nodes\Text;
use VeltAst\Nodes\Button;
use VeltAst\Nodes\Input;
use VeltAst\Nodes\Container;

class VeltParser
{
    private array $tokens = [];  // Liste des tokens
    private int $position = 0;  // Position actuelle dans les tokens

    // Ligne 18-26: Point d'entrée du parsing
    public function parse(string $content, string $view): AST
    {
        $this->tokens = $this->tokenize($content);  // Tokenize le contenu
        $this->position = 0;  // Réinitialise la position

        $root = $this->parseComponent();  // Parse le composant racine

        return new AST($view, $root, ['source' => $view]);  // Retourne l'AST
    }

    // Ligne 28-42: Tokenization du contenu
    private function tokenize(string $content): array
    {
        $tokens = [];
        $lines = explode("\n", $content);  // Split par lignes
        
        foreach ($lines as $line) {
            // Ignore les lignes vides et commentaires
            if ($line === '' || trim($line) === '' || str_starts_with(trim($line), '//')) {
                continue;
            }
            // Conserve la ligne originale avec indentation
            $tokens[] = $line;
        }

        return $tokens;
    }

    // Ligne 44-84: Parse un composant
    private function parseComponent(): ?object
    {
        if ($this->position >= count($tokens)) {
            return null;  // Fin des tokens
        }

        $token = $this->tokens[$this->position];  // Token actuel
        $this->position++;  // Avance

        // Parse le niveau d'indentation
        $indent = strlen($token) - strlen(ltrim($token));
        $token = trim($token);  // Supprime l'indentation

        // Parse le type de composant et les props
        if (preg_match('/^(\w+)(?:\s+(.+))?$/', $token, $matches)) {
            $type = $matches[1];  // Type du composant
            $propsString = $matches[2] ?? '';  // Props string

            $props = $this->parseProps($propsString);  // Parse les props
            $children = [];  // Enfants

            // Parse les enfants (lignes suivantes avec indentation supérieure)
            while ($this->position < count($tokens)) {
                $nextToken = $this->tokens[$this->position];
                $nextIndent = strlen($nextToken) - strlen(ltrim($nextToken));
                
                if ($nextIndent <= $indent) {
                    break;  // Fin des enfants
                }

                $child = $this->parseComponent();  // Parse récursivement
                if ($child !== null) {
                    $children[] = $child;
                }
            }

            return $this->createNode($type, $props, $children);  // Crée le nœud
        }

        return null;
    }

    // Ligne 86-102: Parse les props
    private function parseProps(string $propsString): array
    {
        $props = [];
        
        if ($propsString === '') {
            return $props;
        }

        // Parse les props comme: class="flex-1" text="Hello"
        preg_match_all('/(\w+)="([^"]*)"/', $propsString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $props[$match[1]] = $match[2];  // key => value
        }

        return $props;
    }

    // Ligne 104-121: Crée un nœud selon le type
    private function createNode(string $type, array $props, array $children): object
    {
        return match($type) {
            'VStack' => new VStack($children, $props['class'] ?? '', $props),
            'HStack' => new HStack($children, $props['class'] ?? '', $props),
            'Text' => new Text($props['value'] ?? '', $props['class'] ?? '', $props),
            'Button' => new Button($props['text'] ?? '', $props['class'] ?? '', $props),
            'Input' => new Input(
                $props['name'] ?? '',
                $props['label'] ?? '',
                $props['type'] ?? 'text',
                $props['class'] ?? '',
                $props
            ),
            'Container' => new Container($children, $props['class'] ?? '', $props),
            default => new VStack($children, '', $props)  // Fallback
        };
    }
}
```

**Rôle dans le module :** Transformation du format texte .velt en AST.

**Rôle dans le framework :** Parser du langage de templates Velt.

---

### Module 7: velt-view

#### Fichier: VeltView.php

**Rôle :** Charge les templates et transforme l'AST en JSON.

**Explication ligne par ligne :**

```php
<?php

namespace VeltView;

use VeltParser\VeltParser;
use VeltAst\AST;

class VeltView
{
    private static ?VeltParser $parser = null;  // Parser statique (singleton)
    private static string $templatesPath = '';  // Chemin des templates

    // Ligne 13-16: Configure le chemin des templates
    public static function setTemplatesPath(string $path): void
    {
        self::$templatesPath = rtrim($path, DIRECTORY_SEPARATOR);
    }

    // Ligne 18-21: Configure le parser (pour injection)
    public static function setParser(?VeltParser $parser): void
    {
        self::$parser = $parser;
    }

    // Ligne 23-40: Charge une vue depuis une session
    public static function fromSession(string $view): self
    {
        // Construit le chemin du fichier template
        $templatePath = self::$templatesPath . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.velt';
        
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: $templatePath");
        }

        $content = file_get_contents($templatePath);  // Lit le fichier
        if ($content === false) {
            throw new \RuntimeException("Failed to read template: $templatePath");
        }

        $parser = self::$parser ?? new VeltParser();  // Utilise ou crée le parser
        $ast = $parser->parse($content, $view);  // Parse le template

        return new self($ast);  // Retourne l'instance VeltView
    }

    // Ligne 42-44: Constructeur
    public function __construct(
        private AST $ast  // AST à convertir
    ) {}

    // Ligne 46-57: Convertit en JSON
    public function toJson(): string
    {
        $data = $this->ast->toArray();  // Convertit l'AST en tableau
        $data['schemaVersion'] = '1.0';  // Ajoute la version du schéma
        
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode AST to JSON');
        }

        return $json;  // Retourne le JSON
    }

    // Ligne 59-64: Convertit en tableau
    public function toArray(): array
    {
        $data = $this->ast->toArray();
        $data['schemaVersion'] = '1.0';
        return $data;
    }

    // Ligne 66-69: Accesseur à l'AST
    public function getAst(): AST
    {
        return $this->ast;
    }
}
```

**Rôle dans le module :** Pont entre les fichiers .velt et le JSON final.

**Rôle dans le framework :** Service de chargement et rendu des vues.

---

## Flux de données complet

### Scénario 1: Création d'une session preview via CLI

```
1. Utilisateur exécute: php bin/velt preview auth.login
   ↓
2. CLI (bin/velt) vérifie l'existence du template
   ↓
3. PreviewUrlGenerator::createForView('auth.login')
   ↓
4. PreviewSessionStore::create('auth.login', $baseUrl)
   - Génère ID unique: bin2hex(random_bytes(6))
   - Crée PreviewSession
   - Sauvegarde dans preview_sessions.json
   ↓
5. QRGenerator::generate($url, $id)
   - Génère image QR (SVG)
   - Sauvegarde dans storage/qrcodes/{id}.svg
   ↓
6. CLI affiche:
   - ID de session
   - URL de preview
   - QR payload
   - Chemin de l'image QR
```

### Scénario 2: Récupération du JSON de preview via API

```
1. Mobile scanne le QR code
   ↓
2. Mobile fait: GET /api/preview/{id}
   ↓
3. PreviewController::preview($id)
   ↓
4. PreviewSessionStore::get($id)
   - Récupère la session depuis preview_sessions.json
   ↓
5. Vérifie expiration
   ↓
6. PageRepository::findByView($session->view)
   - Charge le template .velt
   ↓
7. VeltView::fromSession($view)
   - Lit le fichier template
   ↓
8. VeltParser::parse($content, $view)
   - Tokenize le contenu
   - Parse les composants
   - Construit l'AST
   ↓
9. VeltView::toJson()
   - Convertit AST en tableau
   - Ajoute schemaVersion
   - Encode en JSON
   ↓
10. PreviewController retourne le JSON au mobile
```

---

## Intégration dans le framework

### Position dans l'architecture VeltPHP

```
VeltPHP Framework
├── veltphp-kernel/              (Cœur du framework)
│   ├── packages/kernel/         (Kernel principal)
│   │   ├── Contracts/           (Contrats du framework)
│   │   ├── Container/           (Container DI)
│   │   ├── Application/         (Application)
│   │   └── ServiceProvider/      (Service Providers)
│
└── velt-preview/                (Module Preview - Ce module)
    ├── preview-contracts/       (Contrats spécifiques Preview)
    ├── preview-session-store/   (Stockage des sessions)
    ├── preview-endpoints/       (API HTTP)
    ├── preview-qr-cli/          (CLI)
    ├── velt-ast/                (AST)
    ├── velt-parser/             (Parser .velt)
    └── velt-view/               (Chargement vues)
```

### Intégration avec le Kernel

Pour intégrer le module Preview dans le Kernel VeltPHP, il faut :

1. **Ajouter les dépendances** dans le composer.json du kernel
2. **Créer un PreviewServiceProvider** qui enregistre les services
3. **Enregistrer le ServiceProvider** dans l'application

Le module Preview est conçu pour être **optionnel** et **modulaire** :
- Il peut fonctionner indépendamment du kernel
- Il peut être intégré via des contrats
- Il respecte les principes SOLID du framework

### Dépendances entre modules

```
preview-endpoints
  → dépend de: preview-contracts, preview-session-store, velt-view

velt-view
  → dépend de: velt-parser, velt-ast

velt-parser
  → dépend de: velt-ast

preview-qr-cli
  → dépend de: preview-session-store, preview-contracts

preview-session-store
  → indépendant (pas de dépendances internes)

velt-ast
  → indépendant (pas de dépendances internes)

preview-contracts
  → indépendant (pas de dépendances internes)
```

### Avantages de cette architecture

1. **Modularité** : Chaque module a une responsabilité unique
2. **Testabilité** : Les modules peuvent être testés indépendamment
3. **Extensibilité** : Nouveaux composants AST facilement ajoutables
4. **Maintenance** : Modifications isolées à un module
5. **Réutilisabilité** : Modules utilisables dans d'autres contextes

---

## Conclusion

Le module Velt Preview est une implémentation complète d'un système de preview mobile pour le framework VeltPHP. Il respecte les principes de l'ingénierie logicielle moderne :

- **Séparation des responsabilités** (SRP)
- **Ouverture/Fermeture** (OCP)
- **Inversion de dépendances** (DIP)
- **Interface Segregation** (ISP)

Chaque fichier a un rôle précis et documenté, facilitant la maintenance et l'évolution du module.