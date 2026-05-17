<?php

namespace PreviewEndpoints\Renderer;

use PreviewEndpoints\Contracts\JsonRendererInterface;
use PreviewEndpoints\PreviewPage;

class ArrayJsonRenderer implements JsonRendererInterface
{
    public function render(PreviewPage $page): string
    {
        $json = json_encode($page->toArray(), JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to render preview JSON');
        }

        return $json;
    }
}
