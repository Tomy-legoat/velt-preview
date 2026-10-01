<?php

namespace PreviewProtocol\Protocol;

class SessionHeartbeat
{
    private int $lastHeartbeat;
    private int $heartbeatInterval;
    private int $heartbeatTimeout;

    public function __construct(
        int $heartbeatInterval = 30, // seconds between heartbeats
        int $heartbeatTimeout = 90   // seconds before considering session dead
    ) {
        if ($heartbeatInterval <= 0) {
            throw new \InvalidArgumentException('Heartbeat interval must be positive');
        }
        if ($heartbeatTimeout <= $heartbeatInterval) {
            throw new \InvalidArgumentException('Heartbeat timeout must be greater than interval');
        }

        $this->heartbeatInterval = $heartbeatInterval;
        $this->heartbeatTimeout = $heartbeatTimeout;
        $this->lastHeartbeat = time();
    }

    public function recordHeartbeat(): void
    {
        $this->lastHeartbeat = time();
    }

    public function isAlive(): bool
    {
        $elapsed = time() - $this->lastHeartbeat;
        return $elapsed < $this->heartbeatTimeout;
    }

    public function isHeartbeatDue(): bool
    {
        $elapsed = time() - $this->lastHeartbeat;
        return $elapsed >= $this->heartbeatInterval;
    }

    public function getLastHeartbeat(): int
    {
        return $this->lastHeartbeat;
    }

    public function getHeartbeatInterval(): int
    {
        return $this->heartbeatInterval;
    }

    public function getHeartbeatTimeout(): int
    {
        return $this->heartbeatTimeout;
    }

    public function getSecondsSinceLastHeartbeat(): int
    {
        return time() - $this->lastHeartbeat;
    }
}
