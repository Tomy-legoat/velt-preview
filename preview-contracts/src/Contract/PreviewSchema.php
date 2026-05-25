<?php

namespace PreviewContracts\Contract;

class PreviewSchema
{
    public const VERSION = '1.0';

    public static function build(string $screen, array $components, array $meta = []): array
    {
        return [
            'schemaVersion' => self::VERSION,
            'screen' => $screen,
            'components' => $components,
            'meta' => $meta,
        ];
    }
}
