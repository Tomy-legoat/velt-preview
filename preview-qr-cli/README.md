# Preview QR CLI

This module implements issue 03 without adding heavy QR dependencies.

It provides:

- `PreviewUrlGenerator` service.
- session creation via `PreviewSessionStore`.
- `qrPayload` as a scannable preview URL.
- CLI command `php bin/velt preview <view>`.

## Install

```bash
composer install
```

## Run

```bash
php bin/velt preview auth.login
```

Expected output format:

```text
Preview session created:
ID: fgh123
URL: http://127.0.0.1:8000/api/preview/fgh123
QR payload: http://127.0.0.1:8000/api/preview/fgh123
```

If the view is unknown, the command returns a readable error.
