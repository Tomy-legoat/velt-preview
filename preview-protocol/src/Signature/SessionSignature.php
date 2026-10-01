<?php

namespace PreviewProtocol\Signature;

class SessionSignature
{
    private string $secret;
    /** @var array<string,int> */
    private array $usedNonces = [];

    public function __construct(string $secret, private ?NonceStore $nonceStore = null)
    {
        if ($secret === '') {
            throw new \InvalidArgumentException('Secret cannot be empty');
        }
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('Secret must be at least 32 characters');
        }
        $this->secret = $secret;
    }

    public static function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function sign(string $sessionId, string $nonce, int $timestamp, ?int $expiresAt = null): string
    {
        $payload = sprintf('%s|%s|%d|%d', $sessionId, $nonce, $timestamp, $expiresAt ?? 0);
        return hash_hmac('sha256', $payload, $this->secret);
    }

    public function verify(string $sessionId, string $nonce, int $timestamp, string $signature, ?int $expiresAt = null): bool
    {
        $expected = $this->sign($sessionId, $nonce, $timestamp, $expiresAt);
        return hash_equals($expected, $signature);
    }

    public function generateToken(string $sessionId, int $ttlSeconds = 300): array
    {
        $nonce = self::generateNonce();
        $timestamp = time();
        $expiresAt = $timestamp + $ttlSeconds;
        $signature = $this->sign($sessionId, $nonce, $timestamp, $expiresAt);

        return [
            'session_id' => $sessionId,
            'nonce' => $nonce,
            'timestamp' => $timestamp,
            'expires_at' => $expiresAt,
            'signature' => $signature,
        ];
    }

    public function validateToken(array $token, int $clockSkewTolerance = 30): bool
    {
        $requiredKeys = ['session_id', 'nonce', 'timestamp', 'expires_at', 'signature'];
        foreach ($requiredKeys as $key) {
            if (!isset($token[$key])) {
                return false;
            }
        }

        $now = time();
        if (!is_string($token['session_id']) || !is_string($token['nonce']) || !is_string($token['signature'])) {
            return false;
        }
        if (!is_int($token['timestamp']) || !is_int($token['expires_at'])) {
            return false;
        }

        $timestamp = (int) $token['timestamp'];
        $expiresAt = (int) $token['expires_at'];
        if ($expiresAt <= $timestamp) return false;

        // Check expiration
        if ($now > $expiresAt) {
            return false;
        }

        // Check clock skew
        if (abs($now - $timestamp) > $clockSkewTolerance) {
            return false;
        }

        // Verify signature
        $valid = $this->verify(
            $token['session_id'],
            $token['nonce'],
            $timestamp,
            $token['signature'],
            $expiresAt
        );

        if (!$valid) return false;
        if ($this->nonceStore !== null) {
            return $this->nonceStore->consume($token['nonce'], $expiresAt, $now);
        }
        foreach ($this->usedNonces as $nonce => $nonceExpiresAt) if ($nonceExpiresAt < $now) unset($this->usedNonces[$nonce]);
        if (isset($this->usedNonces[$token['nonce']])) return false;
        $this->usedNonces[$token['nonce']] = $expiresAt;
        return true;
    }
}
