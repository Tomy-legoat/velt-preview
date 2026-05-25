<?php

namespace PreviewQrCli\Repository;

use PreviewQrCli\Contracts\ViewRegistryInterface;

class ArrayViewRegistry implements ViewRegistryInterface
{
    /** @var array<string,bool> */
    private array $views = [];

    /** @param string[] $views */
    public function __construct(array $views)
    {
        foreach ($views as $view) {
            $this->views[$view] = true;
        }
    }

    public function exists(string $view): bool
    {
        return isset($this->views[$view]);
    }
}
