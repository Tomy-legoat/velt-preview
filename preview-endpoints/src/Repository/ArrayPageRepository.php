<?php

namespace PreviewEndpoints\Repository;

use PreviewContracts\Contracts\PageRepositoryInterface;
use PreviewContracts\PreviewPage;

class ArrayPageRepository implements PageRepositoryInterface
{
    /** @var array<string,PreviewPage> */
    private array $pages;

    /** @param array<string,PreviewPage> $pages */
    public function __construct(array $pages)
    {
        $this->pages = $pages;
    }

    public function findByView(string $view): ?PreviewPage
    {
        return $this->pages[$view] ?? null;
    }
}
