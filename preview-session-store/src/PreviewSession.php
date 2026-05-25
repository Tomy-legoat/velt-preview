<?php
namespace PreviewSessionStore;

class PreviewSession
{
    public string $id;
    public string $view;
    public string $url;
    public string $createdAt;
    public ?string $expiresAt;

    public function __construct(string $id, string $view, string $url, string $createdAt, ?string $expiresAt = null)
    {
        $this->id = $id;
        $this->view = $view;
        $this->url = $url;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
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
            $data['expiresAt'] ?? null
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
        ];
    }
}
