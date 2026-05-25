<?php

namespace PreviewContracts\Contracts;

use PreviewContracts\PreviewPage;

interface PageRepositoryInterface
{
    public function findByView(string $view): ?\PreviewContracts\PreviewPage;
}
