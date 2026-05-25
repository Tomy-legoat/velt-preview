<?php

namespace PreviewEndpoints\Http;

class Response
{
    public int $statusCode;
    /** @var array<string,string> */
    public array $headers;
    public string $body;

    public function __construct(int $statusCode, string $body, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
    }

    public static function json(array $data, int $statusCode = 200): self
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode JSON response');
        }

        return new self($statusCode, $json, ['Content-Type' => 'application/json']);
    }
}
