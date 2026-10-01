<?php

namespace PreviewProtocol\Validator;

class RequestValidator
{
    private array $allowedOrigins;
    private array $allowedHosts;
    private int $maxPayloadSize;

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedHosts = ['localhost', '127.0.0.1'],
        int $maxPayloadSize = 1048576 // 1MB default
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedHosts = $allowedHosts;
        $this->maxPayloadSize = $maxPayloadSize;
    }

    public function validateOrigin(?string $origin): bool
    {
        if ($origin === null) {
            return true; // Allow requests without origin header for local development
        }

        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        foreach ($this->allowedOrigins as $allowed) {
            if ($this->matchOrigin($origin, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function matchOrigin(string $origin, string $pattern): bool
    {
        if ($pattern === '*') {
            return true;
        }

        $patternUrl = parse_url($pattern);
        $originUrl = parse_url($origin);
        if (
            is_array($patternUrl)
            && is_array($originUrl)
            && isset($patternUrl['scheme'], $patternUrl['host'], $originUrl['scheme'], $originUrl['host'])
            && str_starts_with($patternUrl['host'], '*.')
        ) {
            $domain = substr($patternUrl['host'], 2);
            return $originUrl['scheme'] === $patternUrl['scheme']
                && ($originUrl['host'] === $domain || str_ends_with($originUrl['host'], '.' . $domain));
        }

        // Simple wildcard matching for subdomains
        if (str_starts_with($pattern, '*.')) {
            $domain = substr($pattern, 2);
            return $origin === $domain || str_ends_with($origin, '.' . $domain);
        }

        return $origin === $pattern;
    }

    public function validateHost(?string $host): bool
    {
        if ($host === null) {
            return false;
        }

        foreach ($this->allowedHosts as $allowed) {
            if ($this->matchHost($host, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function matchHost(string $host, string $pattern): bool
    {
        // Remove port if present
        $host = parse_url('http://' . $host, PHP_URL_HOST) ?: $host;

        if ($pattern === '*') {
            return true;
        }

        if (str_starts_with($pattern, '*.')) {
            $domain = substr($pattern, 2);
            return $host === $domain || str_ends_with($host, '.' . $domain);
        }

        return $host === $pattern;
    }

    public function validatePayloadSize(string $payload): bool
    {
        return strlen($payload) <= $this->maxPayloadSize;
    }

    public function validateJsonPayload(string $payload): bool
    {
        if (!$this->validatePayloadSize($payload)) {
            return false;
        }

        $decoded = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return is_array($decoded);
    }

    public function validateNonce(string $nonce): bool
    {
        // Nonce should be 32 hex characters (16 bytes)
        return preg_match('/^[a-f0-9]{32}$/i', $nonce) === 1;
    }

    public function validateSessionId(string $sessionId): bool
    {
        // Session ID should be 12 hex characters (6 bytes)
        return preg_match('/^[a-f0-9]{12}$/i', $sessionId) === 1;
    }

    public function addAllowedOrigin(string $origin): void
    {
        if (!in_array($origin, $this->allowedOrigins, true)) {
            $this->allowedOrigins[] = $origin;
        }
    }

    public function addAllowedHost(string $host): void
    {
        if (!in_array($host, $this->allowedHosts, true)) {
            $this->allowedHosts[] = $host;
        }
    }
}
