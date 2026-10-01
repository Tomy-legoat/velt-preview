<?php

namespace PreviewProtocol\Signature;

/** Atomic file-backed nonce store suitable for multiple PHP workers sharing storage. */
class FileNonceStore implements NonceStore
{
    public function __construct(private string $path)
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create nonce storage directory');
        }
    }

    public function consume(string $nonce, int $expiresAt, int $now): bool
    {
        $handle = fopen($this->path, 'c+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open nonce storage');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock nonce storage');
            }
            $contents = stream_get_contents($handle);
            $entries = is_string($contents) && $contents !== '' ? json_decode($contents, true) : [];
            if (!is_array($entries)) $entries = [];
            foreach ($entries as $key => $expiry) {
                if (!is_int($expiry) || $expiry < $now) unset($entries[$key]);
            }
            if (isset($entries[$nonce])) return false;
            $entries[$nonce] = $expiresAt;
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, json_encode($entries, JSON_THROW_ON_ERROR)) === false || !fflush($handle)) {
                throw new \RuntimeException('Unable to persist nonce storage');
            }
            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
