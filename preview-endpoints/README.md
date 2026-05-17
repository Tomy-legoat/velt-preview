# Preview Endpoints

Ce dossier contient un mini module HTTP autonome pour l'issue 02.

Il expose deux endpoints:

- `GET /api/session/{id}` pour récupérer les métadonnées de session.
- `GET /api/preview/{id}` pour récupérer le JSON de la page Velt associée.

## Structure

- `src/Http/PreviewController.php` - logique du controller.
- `src/Contracts/JsonRendererInterface.php` - contrat du renderer JSON.
- `src/Contracts/PageRepositoryInterface.php` - contrat de chargement de page.
- `src/PreviewPage.php` - modèle de page preview.
- `src/Renderer/ArrayJsonRenderer.php` - renderer JSON concret.
- `src/Repository/ArrayPageRepository.php` - dépôt simple en mémoire pour la démo.
- `public/index.php` - routeur HTTP minimal.
- `tests/PreviewControllerTest.php` - test de succès et d'erreur.

## Installation

Depuis ce dossier:

```bash
composer install
```

Le paquet `velt/preview-session-store` est consommé via un repository local `path`.

## Test

```bash
php tests/PreviewControllerTest.php
```

Résultat attendu:

```text
PreviewController assertions passed.
```

## Lancement HTTP local

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

## Contrat JSON

Le renderer retourne un objet avec:

- `schemaVersion`
- `screen`
- `components`
- `meta`

Les erreurs retournent aussi du JSON, avec un champ `error.code`.
