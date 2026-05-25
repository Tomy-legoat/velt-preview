<?php

namespace PreviewJsonContract\Error;

class PreviewErrorFactory
{
    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    public static function make(string $type, ?string $detail = null): array
    {
        switch ($type) {
            case PreviewErrorType::SESSION_NOT_FOUND:
                return self::error(404, $type, 'Preview session not found', $detail);
            case PreviewErrorType::SESSION_EXPIRED:
                return self::error(410, $type, 'Preview session expired', $detail);
            case PreviewErrorType::PAGE_NOT_FOUND:
                return self::error(404, $type, 'Preview page not found', $detail);
            default:
                return self::error(500, PreviewErrorType::INTERNAL_ERROR, 'Internal preview error', $detail);
        }
    }

    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    private static function error(int $status, string $code, string $message, ?string $detail): array
    {
        $body = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($detail !== null && $detail !== '') {
            $body['error']['detail'] = $detail;
        }

        return [
            'status' => $status,
            'body' => $body,
        ];
    }
}
