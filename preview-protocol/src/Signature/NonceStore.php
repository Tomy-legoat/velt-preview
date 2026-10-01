<?php

namespace PreviewProtocol\Signature;

interface NonceStore
{
    public function consume(string $nonce, int $expiresAt, int $now): bool;
}
