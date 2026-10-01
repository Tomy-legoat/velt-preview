<?php

namespace PreviewProtocol\Tests;

use PHPUnit\Framework\TestCase;
use PreviewProtocol\Protocol\SessionHeartbeat;

class SessionHeartbeatTest extends TestCase
{
    public function testConstructorThrowsOnInvalidInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SessionHeartbeat(0, 90);
    }

    public function testConstructorThrowsOnTimeoutLessThanInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SessionHeartbeat(30, 20);
    }

    public function testConstructorThrowsOnTimeoutEqualsInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SessionHeartbeat(30, 30);
    }

    public function testRecordHeartbeat(): void
    {
        $heartbeat = new SessionHeartbeat(30, 90);
        $initialLast = $heartbeat->getLastHeartbeat();
        
        sleep(1);
        $heartbeat->recordHeartbeat();
        
        $this->assertGreaterThan($initialLast, $heartbeat->getLastHeartbeat());
    }

    public function testIsAliveInitially(): void
    {
        $heartbeat = new SessionHeartbeat(30, 90);
        $this->assertTrue($heartbeat->isAlive());
    }

    public function testIsAliveAfterHeartbeat(): void
    {
        $heartbeat = new SessionHeartbeat(30, 90);
        $heartbeat->recordHeartbeat();
        $this->assertTrue($heartbeat->isAlive());
    }

    public function testIsAliveAfterTimeout(): void
    {
        $heartbeat = new SessionHeartbeat(1, 2);
        $heartbeat->recordHeartbeat();
        sleep(3);
        $this->assertFalse($heartbeat->isAlive());
    }

    public function testIsHeartbeatDueInitially(): void
    {
        $heartbeat = new SessionHeartbeat(30, 90);
        $this->assertFalse($heartbeat->isHeartbeatDue());
    }

    public function testIsHeartbeatDueAfterInterval(): void
    {
        $heartbeat = new SessionHeartbeat(1, 90);
        sleep(2);
        $this->assertTrue($heartbeat->isHeartbeatDue());
    }

    public function testIsHeartbeatDueAfterHeartbeat(): void
    {
        $heartbeat = new SessionHeartbeat(1, 90);
        sleep(2);
        $heartbeat->recordHeartbeat();
        $this->assertFalse($heartbeat->isHeartbeatDue());
    }

    public function testGetLastHeartbeat(): void
    {
        $heartbeat = new SessionHeartbeat(30, 90);
        $last = $heartbeat->getLastHeartbeat();
        $this->assertIsInt($last);
        $this->assertGreaterThan(0, $last);
    }

    public function testGetHeartbeatInterval(): void
    {
        $heartbeat = new SessionHeartbeat(30, 90);
        $this->assertEquals(30, $heartbeat->getHeartbeatInterval());
    }

    public function testGetHeartbeatTimeout(): void
    {
        $heartbeat = new SessionHeartbeat(30, 90);
        $this->assertEquals(90, $heartbeat->getHeartbeatTimeout());
    }

    public function testGetSecondsSinceLastHeartbeat(): void
    {
        $heartbeat = new SessionHeartbeat(30, 90);
        $heartbeat->recordHeartbeat();
        sleep(1);
        $this->assertEquals(1, $heartbeat->getSecondsSinceLastHeartbeat());
    }

    public function testGetSecondsSinceLastHeartbeatInitially(): void
    {
        $heartbeat = new SessionHeartbeat(30, 90);
        $seconds = $heartbeat->getSecondsSinceLastHeartbeat();
        $this->assertGreaterThanOrEqual(0, $seconds);
        $this->assertLessThan(2, $seconds);
    }
}
