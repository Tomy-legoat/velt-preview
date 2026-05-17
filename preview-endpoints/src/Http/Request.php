<?php

namespace PreviewEndpoints\Http;

class Request
{
    public string $method;
    public string $path;

    public function __construct(string $method, string $path)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
    }

    public static function fromGlobals(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return new self($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);
    }
}
