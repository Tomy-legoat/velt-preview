<?php

namespace PreviewProtocol\Tests;

use PHPUnit\Framework\TestCase;
use PreviewProtocol\Signature\SessionSignature;
use PreviewProtocol\Signature\FileNonceStore;

class SessionSignatureTest extends TestCase
{
    private string $testSecret = 'this-is-a-test-secret-key-at-least-32-chars-long';
    private SessionSignature $signature;

    protected function setUp(): void
    {
        $this->signature = new SessionSignature($this->testSecret);
    }

    public function testConstructorThrowsOnEmptySecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SessionSignature('');
    }

    public function testConstructorThrowsOnShortSecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SessionSignature('short');
    }

    public function testGenerateNonceReturns32CharHex(): void
    {
        $nonce = SessionSignature::generateNonce();
        $this->assertEquals(32, strlen($nonce));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/i', $nonce);
    }

    public function testSignReturnsValidSignature(): void
    {
        $sessionId = 'abc123def456';
        $nonce = SessionSignature::generateNonce();
        $timestamp = time();

        $signature = $this->signature->sign($sessionId, $nonce, $timestamp);
        $this->assertEquals(64, strlen($signature)); // SHA256 = 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/i', $signature);
    }

    public function testVerifyValidSignature(): void
    {
        $sessionId = 'abc123def456';
        $nonce = SessionSignature::generateNonce();
        $timestamp = time();

        $signature = $this->signature->sign($sessionId, $nonce, $timestamp);
        $this->assertTrue($this->signature->verify($sessionId, $nonce, $timestamp, $signature));
    }

    public function testVerifyInvalidSignature(): void
    {
        $sessionId = 'abc123def456';
        $nonce = SessionSignature::generateNonce();
        $timestamp = time();

        $signature = $this->signature->sign($sessionId, $nonce, $timestamp);
        $this->assertFalse($this->signature->verify($sessionId, $nonce, $timestamp, 'invalid' . $signature));
    }

    public function testVerifyDifferentSessionId(): void
    {
        $sessionId = 'abc123def456';
        $nonce = SessionSignature::generateNonce();
        $timestamp = time();

        $signature = $this->signature->sign($sessionId, $nonce, $timestamp);
        $this->assertFalse($this->signature->verify('different', $nonce, $timestamp, $signature));
    }

    public function testVerifyDifferentNonce(): void
    {
        $sessionId = 'abc123def456';
        $nonce = SessionSignature::generateNonce();
        $timestamp = time();

        $signature = $this->signature->sign($sessionId, $nonce, $timestamp);
        $this->assertFalse($this->signature->verify($sessionId, 'differentnonce123456789012', $timestamp, $signature));
    }

    public function testVerifyDifferentTimestamp(): void
    {
        $sessionId = 'abc123def456';
        $nonce = SessionSignature::generateNonce();
        $timestamp = time();

        $signature = $this->signature->sign($sessionId, $nonce, $timestamp);
        $this->assertFalse($this->signature->verify($sessionId, $nonce, $timestamp + 1, $signature));
    }

    public function testGenerateToken(): void
    {
        $sessionId = 'abc123def456';
        $token = $this->signature->generateToken($sessionId, 300);

        $this->assertIsArray($token);
        $this->assertArrayHasKey('session_id', $token);
        $this->assertArrayHasKey('nonce', $token);
        $this->assertArrayHasKey('timestamp', $token);
        $this->assertArrayHasKey('expires_at', $token);
        $this->assertArrayHasKey('signature', $token);

        $this->assertEquals($sessionId, $token['session_id']);
        $this->assertEquals(32, strlen($token['nonce']));
        $this->assertIsInt($token['timestamp']);
        $this->assertIsInt($token['expires_at']);
        $this->assertGreaterThan($token['timestamp'], $token['expires_at']);
    }

    public function testValidateValidToken(): void
    {
        $sessionId = 'abc123def456';
        $token = $this->signature->generateToken($sessionId, 300);

        $this->assertTrue($this->signature->validateToken($token));
    }

    public function testValidateExpiredToken(): void
    {
        $sessionId = 'abc123def456';
        $token = $this->signature->generateToken($sessionId, -1); // Already expired

        $this->assertFalse($this->signature->validateToken($token));
    }

    public function testValidateTokenWithClockSkew(): void
    {
        $sessionId = 'abc123def456';
        $token = $this->signature->generateToken($sessionId, 300);
        
        // Simulate clock skew by modifying timestamp
        $token['timestamp'] = time() - 20; // 20 seconds in the past (within tolerance)
        $token['signature'] = $this->signature->sign(
            $token['session_id'],
            $token['nonce'],
            $token['timestamp'],
            $token['expires_at']
        );
        
        $this->assertTrue($this->signature->validateToken($token, 30));
    }

    public function testValidateTokenWithExcessiveClockSkew(): void
    {
        $sessionId = 'abc123def456';
        $token = $this->signature->generateToken($sessionId, 300);
        
        // Simulate excessive clock skew
        $token['timestamp'] = time() - 100; // 100 seconds in the past (beyond tolerance)
        $token['signature'] = $this->signature->sign(
            $token['session_id'],
            $token['nonce'],
            $token['timestamp'],
            $token['expires_at']
        );
        
        $this->assertFalse($this->signature->validateToken($token, 30));
    }

    public function testValidateTokenMissingFields(): void
    {
        $this->assertFalse($this->signature->validateToken([]));
        $this->assertFalse($this->signature->validateToken(['session_id' => 'test']));
        $this->assertFalse($this->signature->validateToken(['session_id' => 'test', 'nonce' => '12345678901234567890123456789012']));
    }

    public function testValidateTamperedToken(): void
    {
        $sessionId = 'abc123def456';
        $token = $this->signature->generateToken($sessionId, 300);
        
        // Tamper with the signature
        $token['signature'] = 'tampered' . substr($token['signature'], 7);
        
        $this->assertFalse($this->signature->validateToken($token));
    }

    public function testReplayAttackDetection(): void
    {
        $sessionId = 'abc123def456';
        $token = $this->signature->generateToken($sessionId, 300);
        
        // First validation should succeed
        $this->assertTrue($this->signature->validateToken($token));
        
        // A signed token is single-use to prevent replay within its validator lifetime.
        $this->assertFalse($this->signature->validateToken($token));
    }

    public function testFileNonceStorePreventsReplayAcrossSignatureInstances(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'preview_nonces_' . bin2hex(random_bytes(6)) . '.json';
        try {
            $first = new SessionSignature($this->testSecret, new FileNonceStore($path));
            $second = new SessionSignature($this->testSecret, new FileNonceStore($path));
            $token = $first->generateToken('abc123def456');
            $this->assertTrue($first->validateToken($token));
            $this->assertFalse($second->validateToken($token));
        } finally {
            if (is_file($path)) unlink($path);
        }
    }
}
