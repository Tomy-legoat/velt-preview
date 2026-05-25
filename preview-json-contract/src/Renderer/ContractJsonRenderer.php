<?php

namespace PreviewJsonContract\Renderer;

use PreviewContracts\Contracts\JsonRendererInterface;
use PreviewContracts\PreviewPage;
use PreviewContracts\Contract\PreviewSchema;

class ContractJsonRenderer implements JsonRendererInterface
{
    public function render(PreviewPage $page): string
    {
        $payload = PreviewSchema::build(
            $page->view,
            $page->components,
            $page->meta
        );

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode contract JSON');
        }

        return $json;
    }
}
