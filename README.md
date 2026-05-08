# Sous-module 06 - Preview API

## Mission

Ce sous-module connecte le monde Web et le monde mobile preview. Il gere les sessions de preview, expose les endpoints JSON et fournit les donnees necessaires au QR code.

Le but du Module 1 n'est pas de faire un Expo Go complet, mais de prouver que Velt peut rendre une meme page en Web et en JSON mobile.

## Perimetre

Inclus :

- session de preview ;
- mapping session vers page Velt ;
- endpoint `GET /api/preview/{id}` ;
- endpoint `GET /api/session/{id}` ;
- payload QR code ;
- stockage fichier simple pour le MVP.

Exclus :

- WebSocket ;
- hot reload ;
- rendu natif complet ;
- app mobile officielle complete.

## Issues

- [Issue 01 - Creer PreviewSession Store](issues/01-creer-preview-session-store.md)
- [Issue 02 - Ajouter endpoints preview](issues/02-ajouter-endpoints-preview.md)
- [Issue 03 - Integrer payload QR code CLI](issues/03-integrer-payload-qr-code-cli.md)

