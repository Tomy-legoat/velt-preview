<?php
namespace PreviewSessionStore;

class PreviewSession
{
    public string $id;
    public string $view;
    public string $url;
    public string $createdAt;
    public ?string $expiresAt;
    public ?string $nonce;
    public ?string $signature;
    public ?int $timestamp;
    public ?array $capabilities;
    public ?int $sequence;
    public ?int $lastHeartbeat;

    public function __construct(
        string $id,
        string $view,
        string $url,
        string $createdAt,
        ?string $expiresAt = null,
        ?string $nonce = null,
        ?string $signature = null,
        ?int $timestamp = null,
        ?array $capabilities = null,
        ?int $sequence = null,
        ?int $lastHeartbeat = null
    ) {
        $this->id = $id;
        $this->view = $view;
        $this->url = $url;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
        $this->nonce = $nonce;
        $this->signature = $signature;
        $this->timestamp = $timestamp;
        $this->capabilities = $capabilities;
        $this->sequence = $sequence;
        $this->lastHeartbeat = $lastHeartbeat;
    }

    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        if ($this->expiresAt === null || $this->expiresAt === '') {
            return false;
        }

        $current = $now ?? new \DateTimeImmutable();
        return $current >= new \DateTimeImmutable($this->expiresAt);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['view'] ?? '',
            $data['url'] ?? '',
            $data['createdAt'] ?? '',
            $data['expiresAt'] ?? null,
            $data['nonce'] ?? null,
            $data['signature'] ?? null,
            $data['timestamp'] ?? null,
            $data['capabilities'] ?? null,
            $data['sequence'] ?? null,
            $data['lastHeartbeat'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'view' => $this->view,
            'url' => $this->url,
            'createdAt' => $this->createdAt,
            'expiresAt' => $this->expiresAt,
            'nonce' => $this->nonce,
            'signature' => $this->signature,
            'timestamp' => $this->timestamp,
            'capabilities' => $this->capabilities,
            'sequence' => $this->sequence,
            'lastHeartbeat' => $this->lastHeartbeat,
        ];
    }
}
