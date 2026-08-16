# Velt Preview

Velt Preview définit le protocole qui permet à une application Velt en cours de développement d’exposer ses écrans à un client de prévisualisation. Le package gère les sessions temporaires, la représentation JSON versionnée des interfaces, les erreurs de protocole et les informations encodées dans le QR code.

Ce dépôt ne prétend pas, à lui seul, être l’équivalent de l’application Expo Go. Il constitue la couche serveur et contractuelle consommée par [`velt-mobile-preview`](https://github.com/Velt-PHP/velt-mobile-preview). Le rendu Android natif, la découverte réseau, la signature des sessions et le transport temps réel doivent être validés conjointement avant une version stable.

> Statut : préversion. Le stockage fichier et le transport HTTP actuel conviennent au développement local, pas à une exposition sur Internet.

## Pourquoi ce package existe

Une page Velt doit pouvoir être rendue sans envoyer de HTML à l’application mobile. Preview transforme une vue déclarative en un arbre portable : le client Android lit les composants, vérifie la version du schéma, puis les associe à ses composants natifs. Cette séparation évite de cacher une WebView derrière une API dite « native ».

```text
Vue Velt déclarative
    -> arbre UI Velt
    -> contrat JSON versionné
    -> session Preview
    -> HTTPS/WebSocket (cible)
    -> renderer Android natif
```

## Capacités actuelles

- création, lecture et expiration logique de sessions de preview ;
- registre de pages indépendant du contrôleur HTTP ;
- réponses JSON pour une session ou une route connue ;
- erreurs structurées pour les sessions et pages inconnues ;
- génération d’URL et de QR codes SVG ;
- schéma JSON explicite avec `schemaVersion`, `screen`, `meta` et `components` ;
- parser expérimental de fichiers `.velt` indentés et AST minimal ;
- tests unitaires des stores, endpoints et contrats.

## Installation

```bash
composer require velt/preview
```

Pour contribuer au dépôt :

```bash
git clone https://github.com/Velt-PHP/velt-preview.git
cd velt-preview
composer install
composer test
```

La ligne de commande publique destinée aux applications reste `velt`. Le binaire interne `velt-preview` est un outil de package et ne remplace pas la CLI officielle.

## Démarrage rapide

Dans une application créée par le skeleton :

```bash
velt serve 0.0.0.0:8000
velt preview 192.168.1.20:8000
```

L’adresse doit être accessible depuis le téléphone. `127.0.0.1` et `localhost` désignent le téléphone lorsqu’ils sont utilisés dans l’application mobile et ne peuvent donc pas joindre le poste de développement.

Une session associe au minimum :

- un identifiant imprévisible ;
- une vue ou route Velt ;
- une URL réseau joignable ;
- une date de création et, à terme, une expiration ;
- la version du protocole et les capacités proposées.

## Contrat JSON

Exemple simplifié :

```json
{
  "schemaVersion": 1,
  "screen": "Connexion",
  "meta": {
    "title": "Connexion",
    "route": "auth.login"
  },
  "components": [
    {
      "type": "Text",
      "props": { "value": "Bienvenue", "variant": "title" },
      "children": []
    },
    {
      "type": "Button",
      "props": { "text": "Continuer", "event": "login.submit" },
      "children": []
    }
  ]
}
```

Règles de compatibilité :

- le client refuse une version majeure de schéma inconnue ;
- les champs obligatoires ne changent pas silencieusement de type ;
- un composant inconnu produit un diagnostic visible, jamais une exécution arbitraire ;
- les événements utilisent des identifiants stables et des payloads validés ;
- le JSON ne contient ni PHP exécutable, ni HTML à charger dans une WebView ;
- chaque évolution incompatible exige une nouvelle version du schéma et des fixtures de compatibilité.

## Endpoints applicatifs

Le skeleton expose actuellement les routes de démonstration suivantes :

| Méthode | Route | Responsabilité |
| --- | --- | --- |
| `GET` | `/api/preview/{id}` | retourne l’arbre UI de la session |
| `GET` | `/api/session/{id}` | retourne les métadonnées de session |
| `GET` | `/api/preview-route/{path}` | rend une route Velt connue en JSON |

Les erreurs doivent conserver une forme déterministe :

```json
{
  "error": {
    "type": "preview_session_not_found",
    "message": "The preview session does not exist.",
    "retryable": false
  }
}
```

Les statuts HTTP attendus sont notamment `400` pour une requête invalide, `404` pour une ressource inconnue, `410` pour une session expirée, `422` pour un payload incompatible et `500` pour une erreur serveur masquée au client.

## Architecture du dépôt

| Dossier | Rôle |
| --- | --- |
| `preview-contracts/` | contrats partagés et types d’erreur |
| `preview-session-store/` | persistance des sessions de développement |
| `preview-endpoints/` | contrôleur et réponses HTTP découplés |
| `preview-qr-cli/` | URL de connexion et QR SVG |
| `preview-json-contract/` | sérialisation et validation du protocole |
| `preview-flow-e2e/` | scénario d’intégration entre les couches |
| `velt-ast/` | arbre syntaxique expérimental |
| `velt-parser/` | parser expérimental du format `.velt` |
| `velt-view/` | chargement des vues et adaptation en page Preview |

Le package conserve actuellement plusieurs espaces de noms historiques. Leur consolidation sous `Velt\Preview\` doit faire l’objet d’une migration documentée afin de ne pas casser les consommateurs existants.

## Format `.velt` expérimental

```velt
VStack class="flex-1 p-4"
  Text value="Se connecter" class="text-2xl font-bold"
  Input name="email" label="Email" type="email"
  Input name="password" label="Mot de passe" type="password"
  Button text="Connexion" event="login.submit"
```

```text
fichier .velt -> VeltParser -> AST -> VeltView -> contrat JSON
```

Ce format n’est pas encore déclaré stable. La syntaxe PHP `.velt.php` de `velt/ui` reste la voie applicative documentée tant que le parser texte ne possède pas de grammaire formelle, de diagnostics de position et de stratégie de migration.

## QR code et connexion

Le QR code encode une URL de session ; il ne doit pas contenir de secret permanent. En développement local, le SVG est écrit dans `storage/qrcodes`. Ces artefacts ne doivent pas être versionnés ni réutilisés comme identifiants de production.

La cible de sécurité comprend :

- jeton éphémère, signé et à usage limité ;
- durée de vie courte et révocation explicite ;
- négociation de version et de capacités avant le rendu ;
- HTTPS/WSS dès que la connexion sort du réseau local contrôlé ;
- absence de chargement de code arbitraire depuis le serveur ;
- liste fermée des événements et API appareil autorisés ;
- logs expurgés des jetons et données sensibles.

## Preview temps réel : cible

Le polling actuel prouve le flux mais ne représente pas l’expérience finale. La cible prévoit :

1. ouverture d’une session signée depuis la CLI ;
2. lecture du QR ou du deep link par le companion ;
3. négociation du schéma et des capacités ;
4. snapshot initial complet ;
5. mises à jour par WebSocket sous forme de diffs ordonnés ;
6. accusés de réception des événements utilisateur ;
7. reconnexion avec backoff et reprise à partir d’un numéro de séquence ;
8. diagnostic clair si un rebuild du client est nécessaire.

## Relation avec le runtime Android

Preview et le runtime embarqué sont deux chemins différents :

- le companion Preview exécute le kernel sur l’ordinateur et reçoit un arbre UI par le réseau ;
- l’APK final embarque PHP et appelle les composants Android via `nativephp_call()` et JNI ;
- aucun des deux chemins ne doit utiliser une WebView comme rendu principal ;
- une fonctionnalité native absente du companion standard nécessite un client de développement personnalisé ;
- le faux bridge de `velt/native` est réservé aux tests PHP.

La spécification complète est maintenue dans [`velt-mobile-architecture`](https://github.com/Velt-PHP/velt-mobile-architecture).

## Tests et qualité

```bash
composer validate --strict
composer install
composer test
```

Avant une version stable, la CI doit également couvrir :

- PHP 8.2, 8.3 et 8.4 sur Linux et Windows ;
- fixtures valides et invalides pour chaque version du schéma ;
- concurrence, corruption et nettoyage du store de sessions ;
- tests E2E contre le vrai `velt/ui` et le vrai routeur `velt/http` ;
- reconnexion, ordre des événements et expiration ;
- tests de sécurité des URL, jetons et payloads ;
- matrice de compatibilité avec chaque version publiée du companion Android.

## Limites connues avant stabilité

- stockage fichier non adapté à plusieurs processus ;
- protocole temps réel et authentification signée non finalisés ;
- parser `.velt` encore expérimental ;
- compatibilité ascendante non automatisée sur plusieurs versions ;
- pas encore de suite Android instrumentée exécutée avec ce dépôt ;
- artefacts de démonstration historiques présents dans certains dossiers de stockage.

Ces limites sont des gates de release : elles ne doivent pas être masquées par un tag stable.

## Contribution

Une modification de protocole doit inclure : tests, fixture JSON, documentation de compatibilité, impact sur `velt-ui`, `velt-mobile-preview` et `velt-native`, ainsi qu’une note de migration lorsque le changement est incompatible. Les issues du dépôt et les milestones de l’organisation indiquent les priorités et échéances.

## Licence

Velt Preview est distribué sous licence MIT.
