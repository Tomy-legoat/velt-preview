<?php

namespace PreviewProtocol\Tests;

use PHPUnit\Framework\TestCase;
use PreviewProtocol\Validator\RequestValidator;

class RequestValidatorTest extends TestCase
{
    private RequestValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RequestValidator(
            ['https://example.com', 'https://*.example.com'],
            ['localhost', '127.0.0.1', 'example.com'],
            1024
        );
    }

    public function testValidateOriginWithWildcard(): void
    {
        $wildcardValidator = new RequestValidator(['*']);
        $this->assertTrue($wildcardValidator->validateOrigin('https://any-origin.com'));
        $this->assertTrue($wildcardValidator->validateOrigin(null));
    }

    public function testValidateOriginWithNull(): void
    {
        $this->assertTrue($this->validator->validateOrigin(null));
    }

    public function testValidateOriginExactMatch(): void
    {
        $this->assertTrue($this->validator->validateOrigin('https://example.com'));
    }

    public function testValidateOriginWildcardMatch(): void
    {
        $this->assertTrue($this->validator->validateOrigin('https://sub.example.com'));
    }

    public function testValidateOriginNoMatch(): void
    {
        $this->assertFalse($this->validator->validateOrigin('https://evil.com'));
    }

    public function testValidateHostExactMatch(): void
    {
        $this->assertTrue($this->validator->validateHost('localhost'));
        $this->assertTrue($this->validator->validateHost('127.0.0.1'));
        $this->assertTrue($this->validator->validateHost('example.com'));
    }

    public function testValidateHostWithPort(): void
    {
        $this->assertTrue($this->validator->validateHost('localhost:8000'));
        $this->assertTrue($this->validator->validateHost('127.0.0.1:8080'));
    }

    public function testValidateHostNoMatch(): void
    {
        $this->assertFalse($this->validator->validateHost('evil.com'));
        $this->assertFalse($this->validator->validateHost(null));
    }

    public function testValidatePayloadSizeWithinLimit(): void
    {
        $payload = str_repeat('a', 500);
        $this->assertTrue($this->validator->validatePayloadSize($payload));
    }

    public function testValidatePayloadSizeExceedsLimit(): void
    {
        $payload = str_repeat('a', 2000);
        $this->assertFalse($this->validator->validatePayloadSize($payload));
    }

    public function testValidateJsonPayloadValid(): void
    {
        $payload = json_encode(['test' => 'data']);
        $this->assertTrue($this->validator->validateJsonPayload($payload));
    }

    public function testValidateJsonPayloadInvalidJson(): void
    {
        $payload = '{invalid json}';
        $this->assertFalse($this->validator->validateJsonPayload($payload));
    }

    public function testValidateJsonPayloadTooLarge(): void
    {
        $payload = json_encode(['data' => str_repeat('a', 2000)]);
        $this->assertFalse($this->validator->validateJsonPayload($payload));
    }

    public function testValidateNonceValid(): void
    {
        $this->assertTrue($this->validator->validateNonce(str_repeat('a', 32)));
    }

    public function testValidateNonceInvalidTooShort(): void
    {
        $this->assertFalse($this->validator->validateNonce('short'));
    }

    public function testValidateNonceInvalidTooLong(): void
    {
        $this->assertFalse($this->validator->validateNonce('abc123def456789012345678901234567890'));
    }

    public function testValidateNonceInvalidCharacters(): void
    {
        $this->assertFalse($this->validator->validateNonce('invalid-chars-123456789012345'));
    }

    public function testValidateSessionIdValid(): void
    {
        $this->assertTrue($this->validator->validateSessionId('abc123def456'));
    }

    public function testValidateSessionIdInvalidTooShort(): void
    {
        $this->assertFalse($this->validator->validateSessionId('short'));
    }

    public function testValidateSessionIdInvalidTooLong(): void
    {
        $this->assertFalse($this->validator->validateSessionId('abc123def45678'));
    }

    public function testValidateSessionIdInvalidCharacters(): void
    {
        $this->assertFalse($this->validator->validateSessionId('invalid-chars'));
    }

    public function testAddAllowedOrigin(): void
    {
        $this->validator->addAllowedOrigin('https://new-origin.com');
        $this->assertTrue($this->validator->validateOrigin('https://new-origin.com'));
    }

    public function testAddAllowedHost(): void
    {
        $this->validator->addAllowedHost('new-host.com');
        $this->assertTrue($this->validator->validateHost('new-host.com'));
    }

    public function testAddAllowedOriginNoDuplicate(): void
    {
        $this->validator->addAllowedOrigin('https://example.com');
        $this->validator->addAllowedOrigin('https://example.com');
        // Should still work, no error
        $this->assertTrue($this->validator->validateOrigin('https://example.com'));
    }
}
