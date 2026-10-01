<?php

namespace PreviewContracts\Contract;

class PreviewSchema
{
    public const VERSION = '1.0';

    public static function build(string $screen, array $components, array $meta = [], ?array $capabilities = null): array
    {
        $schema = [
            'schemaVersion' => self::VERSION,
            'screen' => $screen,
            'components' => $components,
            'meta' => $meta,
        ];

        if ($capabilities !== null) {
            $schema['capabilities'] = $capabilities;
        }

        return $schema;
    }

    public static function withProtocol(array $schema, string $protocolVersion, array $capabilities): array
    {
        $schema['protocolVersion'] = $protocolVersion;
        $schema['capabilities'] = $capabilities;
        return $schema;
    }
}
