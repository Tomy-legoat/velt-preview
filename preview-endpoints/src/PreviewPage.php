<?php

namespace PreviewEndpoints;

class PreviewPage
{
    public string $view;
    /** @var array<int,array<string,mixed>> */
    public array $components;
    /** @var array<string,mixed> */
    public array $meta;

    public function __construct(string $view, array $components, array $meta = [])
    {
        $this->view = $view;
        $this->components = $components;
        $this->meta = $meta;
    }

    /** @return array{schemaVersion:int,screen:string,components:array<int,array<string,mixed>>,meta:array<string,mixed>} */
    public function toArray(): array
    {
        return [
            'schemaVersion' => 1,
            'screen' => $this->view,
            'components' => $this->components,
            'meta' => $this->meta,
        ];
    }
}
