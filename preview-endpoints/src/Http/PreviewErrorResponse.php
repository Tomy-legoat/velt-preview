<?php

namespace PreviewEndpoints\Http;

class PreviewErrorResponse
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $detail = null,
    ) {
    }

    public static function sessionNotFound(?string $detail = null): self
    {
        return new self('SESSION_NOT_FOUND', 'Preview session not found', $detail);
    }

    public static function sessionExpired(?string $detail = null): self
    {
        return new self('SESSION_EXPIRED', 'Preview session expired', $detail);
    }

    public static function pageNotFound(?string $detail = null): self
    {
        return new self('PAGE_NOT_FOUND', 'Preview page not found', $detail);
    }

    public static function methodNotAllowed(?string $detail = null): self
    {
        return new self('METHOD_NOT_ALLOWED', 'Method not allowed', $detail);
    }

    public static function notFound(?string $detail = null): self
    {
        return new self('NOT_FOUND', 'Route not found', $detail);
    }

    public static function internalError(?string $detail = null): self
    {
        return new self('INTERNAL_ERROR', 'Internal preview error', $detail);
    }

    /**
     * @return array{error: array{code: string, message: string, detail?: string}}
     */
    public function toArray(): array
    {
        $error = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if ($this->detail !== null && $this->detail !== '') {
            $error['detail'] = $this->detail;
        }

        return ['error' => $error];
    }
}
