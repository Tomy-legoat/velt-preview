<?php

namespace PreviewTransport\Transport;

use PreviewTransport\Message\ProtocolMessage;

class HttpTransport implements TransportLayer
{
    private bool $connected = false;
    private string $baseUrl;
    private ?string $sessionId = null;

    public function __construct(string $baseUrl, ?string $sessionId = null, private int $timeoutSeconds = 10, private ?array $authToken = null)
    {
        if ($timeoutSeconds < 1) throw new \InvalidArgumentException('HTTP timeout must be positive');
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->sessionId = $sessionId;
    }

    public function send(ProtocolMessage $message): void
    {
        $this->sessionId = $message->sessionId ?? $this->sessionId;
        if ($this->sessionId === null) throw new \InvalidArgumentException('A session ID is required to send a protocol message');
        $body = null;
        $method = 'POST';
        $path = match ($message->type) {
            'heartbeat' => '/api/session/' . $this->sessionId . '/heartbeat',
            'session_resume' => '/api/session/' . $this->sessionId . '/resume',
            'capability_negotiation' => '/api/session/' . $this->sessionId . '/negotiate',
            default => throw new \InvalidArgumentException('HTTP transport does not support outbound message type: ' . $message->type),
        };
        if ($message->type === 'session_resume') $body = json_encode(['sequence' => $message->sequence], JSON_THROW_ON_ERROR);
        if ($message->type === 'capability_negotiation') $body = json_encode($message->payload, JSON_THROW_ON_ERROR);
        $url = $this->baseUrl . $path;
        if ($this->authToken !== null) $url .= '?' . http_build_query($this->authToken, '', '&', PHP_QUERY_RFC3986);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(5, $this->timeoutSeconds));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
        
        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($handle, string $line) use (&$responseHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            return strlen($line);
        });
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result === false || $status >= 400) {
            curl_close($ch);
            throw new \RuntimeException('Unable to send protocol message (HTTP ' . $status . ')');
        }
        curl_close($ch);
        if (isset($responseHeaders['x-preview-token'])) {
            $decoded = base64_decode($responseHeaders['x-preview-token'], true);
            $nextToken = is_string($decoded) ? json_decode($decoded, true) : null;
            if (is_array($nextToken)) $this->authToken = $nextToken;
        }
    }

    public function receive(): ?ProtocolMessage
    {
        if (!$this->connected || $this->sessionId === null) {
            return null;
        }

        $url = $this->baseUrl . '/api/preview/' . $this->sessionId;
        if ($this->authToken !== null) $url .= '?' . http_build_query($this->authToken, '', '&', PHP_QUERY_RFC3986);
        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(5, $this->timeoutSeconds));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($handle, string $line) use (&$responseHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            return strlen($line);
        });
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false || $status >= 400) {
            return null;
        }
        if (isset($responseHeaders['x-preview-token'])) {
            $rotated = base64_decode($responseHeaders['x-preview-token'], true);
            $decodedToken = is_string($rotated) ? json_decode($rotated, true) : null;
            if (is_array($decodedToken)) $this->authToken = $decodedToken;
        }

        $payload = json_decode($result, true);
        if (!is_array($payload) || isset($payload['error'])) {
            return null;
        }

        return ProtocolMessage::createUiSnapshot($payload, 0, $this->sessionId);
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function connect(): void
    {
        $this->connected = true;
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }
}
