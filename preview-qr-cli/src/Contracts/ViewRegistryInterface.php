<?php

namespace PreviewQrCli\Contracts;

interface ViewRegistryInterface
{
    public function exists(string $view): bool;
}
