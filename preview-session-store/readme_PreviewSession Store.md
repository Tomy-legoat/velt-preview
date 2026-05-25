# PreviewSession Store

Ce module minimal fournit un stockage de sessions preview en fichier JSON pour le MVP de Preview.

Fichiers créés:

- `src/PreviewSession.php` — modèle de donnée `id`, `view`, `url`, `createdAt`.
- `src/PreviewSession.php` — modèle de donnée `id`, `view`, `url`, `createdAt`, `expiresAt`.
- `src/PreviewSessionStore.php` — lecture/écriture JSON, création, lecture, suppression, purge des expirées.
- `src/Exceptions/PreviewSessionNotFoundException.php` — exception documentée pour `getOrFail()`.
- `tests/PreviewSessionStoreTest.php` — tests basiques utilisant le filesystem temporaire.
- `composer.json` — autoload PSR-4.

Usage rapide:

1. Installer les dépendances (installer php + composer si nécessaire), puis:

```bash
composer install
```

2. Charger l'autoload et utiliser le store:

```php
require 'vendor/autoload.php';
use PreviewSessionStore\PreviewSessionStore;

$store = new PreviewSessionStore(__DIR__ . '/data');
$session = $store->create('auth.login', 'http://127.0.0.1:8000');
echo $session->id;
//pour les test
/*echo "File: " . __DIR__ . "/data/preview_sessions.json\n";*/
```

Exécuter le test (mode simple sans phpunit):

```bash
php tests/PreviewSessionStoreTest.php
```

Remarques:

- Les IDs sont générés via `bin2hex(random_bytes(6))` (non prédictible, courts).
- Le format du fichier JSON est une map d'objets indexés par `id` pour garder le fichier lisible.
- `get()` retourne `null` si absent; `getOrFail()` jette `PreviewSessionNotFoundException`.
- `create()` accepte un TTL optionnel en secondes pour ajouter `expiresAt`.
