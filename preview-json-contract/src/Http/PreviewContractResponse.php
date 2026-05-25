<?php

namespace PreviewJsonContract\Http;

use PreviewEndpoints\Http\Response;
use PreviewJsonContract\Error\PreviewErrorFactory;

class PreviewContractResponse
{
    public static function success(array $payload): Response
    {
        return Response::json($payload, 200);
    }

    public static function error(string $type, ?string $detail = null): Response
    {
        $err = PreviewErrorFactory::make($type, $detail);
        return Response::json($err['body'], $err['status']);
    }
}
