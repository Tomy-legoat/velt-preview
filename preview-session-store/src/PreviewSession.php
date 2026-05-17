<?php
namespace PreviewSessionStore;

class PreviewSession
{
    public string $id;
    public string $view;
    public string $url;
    public string $createdAt;

    public function __construct(string $id, string $view, string $url, string $createdAt)
    {
        $this->id = $id;
        $this->view = $view;
        $this->url = $url;
        $this->createdAt = $createdAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['view'] ?? '',
            $data['url'] ?? '',
            $data['createdAt'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'view' => $this->view,
            'url' => $this->url,
            'createdAt' => $this->createdAt,
        ];
    }
}
