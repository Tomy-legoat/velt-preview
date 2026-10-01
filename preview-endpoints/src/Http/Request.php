<?php

namespace PreviewEndpoints\Http;

class Request
{
    public string $method;
    public string $path;
    /** @var array<string,mixed> */
    public array $query;
    public string $body;
    public ?string $origin;
    public ?string $host;

    public function __construct(string $method, string $path, array $query = [], string $body = '', ?string $origin = null, ?string $host = null)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
        $this->origin = $origin;
        $this->host = $host;
    }

    public static function fromGlobals(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $uri,
            $_GET,
            (string) file_get_contents('php://input'),
            $_SERVER['HTTP_ORIGIN'] ?? null,
            $_SERVER['HTTP_HOST'] ?? null
        );
    }
}
