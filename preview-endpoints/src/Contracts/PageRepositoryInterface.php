<?php

namespace PreviewEndpoints\Contracts;

use PreviewEndpoints\PreviewPage;

interface PageRepositoryInterface
{
    public function findByView(string $view): ?PreviewPage;
}
