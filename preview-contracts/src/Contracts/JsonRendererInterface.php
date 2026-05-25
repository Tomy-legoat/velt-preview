<?php

namespace PreviewContracts\Contracts;

use PreviewContracts\PreviewPage;

interface JsonRendererInterface
{
    public function render(\PreviewContracts\PreviewPage $page): string;
}
