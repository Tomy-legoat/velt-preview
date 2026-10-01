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

    public static function invalidSignature(?string $detail = null): self
    {
        return new self('INVALID_SIGNATURE', 'Preview authentication failed', $detail);
    }

    public static function invalidPayload(?string $detail = null): self
    {
        return new self('INVALID_PAYLOAD', 'Preview payload is invalid', $detail);
    }

    public static function protocolMismatch(?string $detail = null): self
    {
        return new self('PROTOCOL_MISMATCH', 'Preview protocol major version is incompatible', $detail);
    }

    public static function capabilityNegotiationFailed(?string $detail = null): self
    {
        return new self('CAPABILITY_NEGOTIATION_FAILED', 'Preview capabilities could not be negotiated', $detail);
    }

    public static function invalidOrigin(?string $detail = null): self
    {
        return new self('INVALID_ORIGIN', 'Preview origin is not allowed', $detail);
    }

    public static function invalidHost(?string $detail = null): self
    {
        return new self('INVALID_HOST', 'Preview host is not allowed', $detail);
    }

    public static function payloadTooLarge(?string $detail = null): self
    {
        return new self('PAYLOAD_TOO_LARGE', 'Preview payload is too large', $detail);
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
