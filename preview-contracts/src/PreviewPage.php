<?php

namespace PreviewContracts;

class PreviewPage
{
    public string $view;
    public array $components;
    public array $meta;

    public function __construct(string $view, array $components = [], array $meta = [])
    {
        $this->view = $view;
        $this->components = $components;
        $this->meta = $meta;
    }

    public function toArray(): array
    {
        return [
            'screen' => $this->view,
            'components' => $this->components,
            'meta' => $this->meta,
        ];
    }
}
