# Preview Flow E2E

This module implements issue 04 as an executable integration flow runner (without test files).

It validates the full chain in one run:

1. create preview session
2. call preview controller
3. render JSON payload
4. check missing session case
5. check missing page case

No external HTTP server and no WebSocket are required.

## Install

```bash
composer install
```

## Run flow

```bash
php bin/preview-flow
```

Expected key output:

- Happy path status: `200`
- Missing session status: `404`
- Missing page status: `404`
- payload includes `screen`, `schemaVersion`, `components`
