<?php

namespace PreviewEndpoints\Repository;

use PreviewEndpoints\Contracts\PageRepositoryInterface;
use PreviewEndpoints\PreviewPage;

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
