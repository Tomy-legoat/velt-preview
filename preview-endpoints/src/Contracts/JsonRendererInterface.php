<?php

namespace PreviewEndpoints\Contracts;

use PreviewEndpoints\PreviewPage;

interface JsonRendererInterface
{
    public function render(PreviewPage $page): string;
}
