# Preview JSON Contract

This module implements issue 05 with a stable JSON contract and normalized error payloads.

## Stable success contract

Mandatory fields:

- `schemaVersion`
- `screen`
- `components`
- `meta`

Success example:

```json
{
  "schemaVersion": "1.0",
  "screen": "Login",
  "components": [],
  "meta": {
    "source": "auth.login"
  }
}
```

## Error contract

Error payload format:

```json
{
  "error": {
    "code": "SESSION_NOT_FOUND",
    "message": "Preview session not found"
  }
}
```

The HTTP layer in `preview-endpoints` now uses a dedicated `PreviewErrorResponse` helper so the same error shape is emitted for 404, 410, 405, 500 and route-not-found cases.

Status mapping:

- `200` success
- `404` session not found
- `410` session expired
- `404` page not found
- `500` internal error

The HTTP layer can now distinguish an expired session from a missing one by returning `410 SESSION_EXPIRED` when `expiresAt` is reached.

## Files

- `src/Contract/PreviewSchema.php` - stable payload builder.
- `src/Renderer/ContractJsonRenderer.php` - renderer aligned on contract.
- `src/Error/PreviewErrorFactory.php` - error mapping and payload.
- `src/Http/PreviewContractResponse.php` - helper for HTTP responses.

## Install

```bash
composer install
```

## Demo

```bash
php bin/contract-demo
```
