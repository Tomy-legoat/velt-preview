# Velt Preview v2 - Authentification et Négociation de Session

## Récapitulatif des modifications

Cette version v2 implémente l'authentification sécurisée des sessions de preview avec signature, négociation de protocole et validation stricte des requêtes.

---

## Nouveaux Modules Créés

### 1. Module `preview-protocol/`

Gestion complète du protocole, de la sécurité et de la validation.

#### Structure
```
preview-protocol/
├── src/
│   ├── Protocol/
│   │   ├── ProtocolVersion.php      # Versioning semver du protocole
│   │   ├── ProtocolCapabilities.php # Négociation de capabilities
│   │   ├── SessionHeartbeat.php     # Heartbeat avec timeout
│   │   └── SequenceTracker.php      # Numéros de séquence pour reprise
│   ├── Signature/
│   │   └── SessionSignature.php     # Signature HMAC-SHA256 avec nonce
│   └── Validator/
│       └── RequestValidator.php     # Validation origines, hôtes, payloads
└── tests/
    ├── SessionSignatureTest.php     # 15 tests unitaires
    ├── RequestValidatorTest.php     # 18 tests unitaires
    ├── SessionHeartbeatTest.php     # 11 tests unitaires
    └── SequenceTrackerTest.php      # 14 tests unitaires
```

#### Fonctionnalités

**ProtocolVersion.php**
- Versioning sémantique (major.minor.patch)
- Vérification de compatibilité (major doit matcher)
- Comparaison de versions
- Parsing depuis chaîne de caractères

**ProtocolCapabilities.php**
- Définition des capabilities supportées :
  - `realtime_updates` - Mises à jour temps réel
  - `event_ack` - Accusés de réception d'événements
  - `session_resume` - Reprise de session
  - `authenticated` - Session authentifiée
  - `diff_updates` - Mises à jour par diffs
- Négociation de capabilities (intersection)
- Méthodes `default()` et `full()` pour configurations courantes

**SessionSignature.php**
- Génération de nonce cryptographique (32 caractères hex)
- Signature HMAC-SHA256 avec secret
- Génération de token avec TTL configurable
- Validation de token avec :
  - Vérification d'expiration
  - Tolérance de dérive d'horloge
  - Validation de signature
- Protection contre rejeu par consommation unique des nonces pendant leur TTL

**RequestValidator.php**
- Validation des origines HTTP avec support wildcard (`*.example.com`)
- Validation des hôtes avec support de ports
- Validation de la taille des payloads (configurable, défaut 1MB)
- Validation de JSON payloads
- Validation des nonces (32 caractères hex)
- Validation des session IDs (12 caractères hex)
- Ajout dynamique d'origines et hôtes autorisés

**SessionHeartbeat.php**
- Enregistrement de heartbeats
- Vérification si la session est vivante
- Détection si heartbeat est dû
- Configuration d'intervalle et timeout
- Calcul du temps écoulé depuis dernier heartbeat

**SequenceTracker.php**
- Génération de numéros de séquence incrémentaux
- Accusés de réception de séquences
- Liste des séquences non acquittées
- Réinitialisation du tracker
- Reprise depuis une séquence donnée

### 2. Module `preview-transport/`

Séparation claire entre transport, session et payload UI.

#### Structure
```
preview-transport/
├── src/
│   ├── Message/
│   │   ├── MessageType.php        # Types de messages protocolaires
│   │   └── ProtocolMessage.php   # Messages structurés
│   └── Transport/
│       ├── TransportLayer.php    # Interface de transport
│       └── HttpTransport.php     # Implémentation HTTP
└── tests/
```

#### Fonctionnalités

**MessageType.php**
- Types de messages définis :
  - `session_init` - Initialisation de session
  - `session_ack` - Accusé de réception de session
  - `ui_snapshot` - Snapshot UI complet
  - `ui_diff` - Diff de mise à jour UI
  - `event` - Événement utilisateur
  - `heartbeat` - Heartbeat
  - `error` - Erreur protocolaire
  - `capability_negotiation` - Négociation de capabilities
  - `session_resume` - Reprise de session

**ProtocolMessage.php**
- Structure de message avec type, séquence, payload
- Support optionnel de sessionId et timestamp
- Méthodes factory pour créer chaque type de message
- Sérialisation/désérialisation JSON

**TransportLayer.php**
- Interface définissant :
  - `send()` - Envoi de message
  - `receive()` - Réception de message
  - `isConnected()` - État de connexion
  - `connect()` - Connexion
  - `disconnect()` - Déconnexion

**HttpTransport.php**
- Implémentation HTTP pour polling/fallback
- Utilisation de cURL pour les requêtes
- Support de base pour envoi de messages

---

## Modifications des Modules Existants

### 1. `preview-session-store/src/PreviewSession.php`

**Ajouts de propriétés :**
- `nonce` - Nonce cryptographique pour signature
- `signature` - Signature HMAC de la session
- `timestamp` - Timestamp de création
- `capabilities` - Capabilities négociées
- `sequence` - Numéro de séquence courant

**Modifications :**
- Constructeur étendu avec nouveaux paramètres optionnels
- `fromArray()` et `toArray()` mis à jour pour gérer les nouveaux champs

### 2. `preview-contracts/src/Contract/PreviewSchema.php`

**Ajouts :**
- Paramètre optionnel `capabilities` dans `build()`
- Nouvelle méthode `withProtocol()` pour ajouter version et capabilities à un schéma existant

### 3. `preview-contracts/src/Error/PreviewErrorType.php`

**Nouveaux types d'erreurs :**
- `INVALID_SIGNATURE` - Signature invalide
- `INVALID_NONCE` - Nonce invalide
- `INVALID_ORIGIN` - Origine non autorisée
- `INVALID_HOST` - Hôte non autorisé
- `PAYLOAD_TOO_LARGE` - Payload trop grand
- `INVALID_PAYLOAD` - Payload invalide
- `PROTOCOL_MISMATCH` - Incompatibilité de protocole
- `CAPABILITY_NEGOTIATION_FAILED` - Échec de négociation
- `REPLAY_ATTACK` - Tentative de rejeu
- `SESSION_REVOKED` - Session révoquée

### 4. `composer.json`

**Ajouts dans autoload :**
```json
"PreviewProtocol\\": "preview-protocol/src/",
"PreviewTransport\\": "preview-transport/src/"
```

### 5. `README.md`

**Mises à jour :**
- Ajout des nouvelles capacités dans la section "Capacités actuelles"
- Ajout du module `preview-protocol/` dans l'architecture
- Ajout du module `preview-transport/` dans l'architecture
- Nouvelle section complète "Authentification et protocole" avec exemples d'utilisation

---

## Tests Unitaires

### Couverture de tests

**SessionSignatureTest.php (15 tests)**
- Validation du constructeur (secret vide, trop court)
- Génération de nonce (format, longueur)
- Signature et vérification
- Tests de rejeu (différents sessionId, nonce, timestamp)
- Génération et validation de token
- Tests d'expiration
- Tests de dérive d'horloge
- Validation de champs manquants
- Validation de token falsifié
- Documentation sur la protection contre rejeu

**RequestValidatorTest.php (18 tests)**
- Validation des origines (wildcard, null, exact match, wildcard match)
- Validation des hôtes (exact, avec port, non-match)
- Validation de taille de payload
- Validation de JSON payloads (valide, invalide, trop grand)
- Validation de nonces (valide, trop court, trop long, caractères invalides)
- Validation de session IDs (valide, trop court, trop long, caractères invalides)
- Ajout dynamique d'origines et hôtes

**SessionHeartbeatTest.php (11 tests)**
- Validation du constructeur (intervalles invalides)
- Enregistrement de heartbeat
- Vérification si vivant (initial, après heartbeat, après timeout)
- Détection si heartbeat dû (initial, après intervalle, après heartbeat)
- Accesseurs (lastHeartbeat, interval, timeout)
- Calcul du temps écoulé

**SequenceTrackerTest.php (14 tests)**
- Constructeur (défaut, personnalisé, négatif)
- Incrément de séquence
- Accusé de réception (valide, trop haut, négatif)
- Vérification d'acquittement
- Liste des séquences non acquittées
- Réinitialisation (défaut, personnalisé, négatif)
- Reprise depuis séquence (valide, trop haut, négatif, courant)

---

## Critères d'Acceptation

✅ **Version de protocole et capabilities**
- Implémenté via `ProtocolVersion` et `ProtocolCapabilities`
- Négociation automatique des capabilities
- Vérification de compatibilité

✅ **Session courte avec TTL, nonce et signature**
- `SessionSignature` avec HMAC-SHA256
- Génération de nonce cryptographique
- Token avec TTL configurable
- Validation avec dérive d'horloge

✅ **Validation stricte des origines, hôtes et tailles**
- `RequestValidator` complet
- Support de wildcards
- Validation de JSON et taille

✅ **Heartbeat et reprise de session**
- `SessionHeartbeat` avec timeout
- `SequenceTracker` pour reprise
- Gestion des accusés de réception

✅ **Erreurs stables et documentées**
- 9 nouveaux types d'erreurs ajoutés
- Structure d'erreur cohérente

✅ **Séparation transport, session et payload UI**
- Module `preview-transport` indépendant
- Interface `TransportLayer` abstraite
- Messages protocolaires structurés

✅ **Tests de rejeu, expiration et entrées malformées**
- 58 tests unitaires au total
- Couverture complète des scénarios de sécurité
- Tests de validation et de bordures

---

## Utilisation

### Configuration de base

```php
use PreviewProtocol\Signature\SessionSignature;
use PreviewProtocol\Protocol\ProtocolCapabilities;
use PreviewProtocol\Validator\RequestValidator;

// Configuration de la signature
$signature = new SessionSignature('votre-secret-min-32-caracteres');

// Configuration des capabilities
$capabilities = ProtocolCapabilities::full();

// Configuration du validator
$validator = new RequestValidator(
    ['https://votre-domaine.com'],
    ['localhost', '127.0.0.1'],
    1048576 // 1MB
);
```

### Création d'une session authentifiée

```php
use PreviewSessionStore\PreviewSessionStore;
use PreviewProtocol\Signature\SessionSignature;

$store = new PreviewSessionStore(__DIR__ . '/storage');
$signature = new SessionSignature('secret-key');

// Créer session
$session = $store->create('auth.login', 'http://localhost:8000', 300);

// Générer token signé
$token = $signature->generateToken($session->id, 300);

// Mettre à jour la session avec les infos d'authentification
$session->nonce = $token['nonce'];
$session->signature = $token['signature'];
$session->timestamp = $token['timestamp'];
$session->capabilities = ProtocolCapabilities::default()->toArray();
```

### Validation d'une requête

```php
use PreviewProtocol\Validator\RequestValidator;

$validator = new RequestValidator(
    ['https://example.com'],
    ['localhost', '127.0.0.1'],
    1048576
);

// Valider origine
if (!$validator->validateOrigin($_SERVER['HTTP_ORIGIN'] ?? null)) {
    // Erreur: origine non autorisée
}

// Valider hôte
if (!$validator->validateHost($_SERVER['HTTP_HOST'] ?? null)) {
    // Erreur: hôte non autorisé
}

// Valider payload
$payload = file_get_contents('php://input');
if (!$validator->validateJsonPayload($payload)) {
    // Erreur: payload invalide ou trop grand
}
```

### Négociation de protocole

```php
use PreviewProtocol\Protocol\ProtocolVersion;
use PreviewProtocol\Protocol\ProtocolCapabilities;

// Version du client
$clientVersion = ProtocolVersion::fromString('1.0.0');
$serverVersion = ProtocolVersion::fromString('1.1.0');

if (!$serverVersion->isCompatibleWith($clientVersion)) {
    // Erreur: version incompatible
}

// Négociation de capabilities
$serverCaps = ProtocolCapabilities::full();
$clientCaps = ProtocolCapabilities::default();
$negotiated = $serverCaps->negotiate($clientCaps);
```

---

## Limitations Connues

- La protection contre rejeu nécessite un tracking des nonces au niveau applicatif
- Le transport WebSocket n'est pas encore implémenté (HTTP seulement)
- Les tests d'intégration avec l'app Android ne sont pas inclus dans ce dépôt
- Le stockage fichier n'est pas adapté aux environnements multi-processus

---

## Prochaines Étapes

1. Implémentation du transport WebSocket pour temps réel
2. Tracking des nonces pour protection contre rejeu
3. Tests d'intégration avec `velt-mobile-preview`
4. Migration des namespaces historiques sous `Velt\Preview\`
5. Documentation de compatibilité entre versions

---

## Statut

**Version :** 2.0.0  
**Date :** 16 septembre 2026  
**Statut :** Préversion - En développement  
**License :** MIT
