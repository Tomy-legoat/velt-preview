<?php

namespace PreviewJsonContract\Contract;

class PreviewSchema
{
    public const VERSION = '1.0';

    /**
     * @param array<int,array<string,mixed>> $components
     * @param array<string,mixed> $meta
     * @return array{schemaVersion:string,screen:string,components:array<int,array<string,mixed>>,meta:array<string,mixed>}
     */
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
