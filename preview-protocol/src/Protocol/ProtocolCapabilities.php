<?php

namespace PreviewProtocol\Protocol;

class ProtocolCapabilities
{
    public const CAPABILITY_REALTIME_UPDATES = 'realtime_updates';
    public const CAPABILITY_EVENT_ACK = 'event_ack';
    public const CAPABILITY_SESSION_RESUME = 'session_resume';
    public const CAPABILITY_AUTHENTICATED = 'authenticated';
    public const CAPABILITY_DIFF_UPDATES = 'diff_updates';

    private array $capabilities;

    public function __construct(array $capabilities = [])
    {
        $validCapabilities = [
            self::CAPABILITY_REALTIME_UPDATES,
            self::CAPABILITY_EVENT_ACK,
            self::CAPABILITY_SESSION_RESUME,
            self::CAPABILITY_AUTHENTICATED,
            self::CAPABILITY_DIFF_UPDATES,
        ];

        foreach ($capabilities as $cap) {
            if (!in_array($cap, $validCapabilities, true)) {
                throw new \InvalidArgumentException("Unknown capability: $cap");
            }
        }

        $this->capabilities = array_unique($capabilities);
    }

    public static function default(): self
    {
        return new self([
            self::CAPABILITY_AUTHENTICATED,
        ]);
    }

    public static function full(): self
    {
        return new self([
            self::CAPABILITY_REALTIME_UPDATES,
            self::CAPABILITY_EVENT_ACK,
            self::CAPABILITY_SESSION_RESUME,
            self::CAPABILITY_AUTHENTICATED,
            self::CAPABILITY_DIFF_UPDATES,
        ]);
    }

    public function has(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    public function toArray(): array
    {
        return $this->capabilities;
    }

    public function negotiate(ProtocolCapabilities $clientCapabilities): ProtocolCapabilities
    {
        $intersection = array_intersect($this->capabilities, $clientCapabilities->toArray());
        return new self(array_values($intersection));
    }

    public function equals(ProtocolCapabilities $other): bool
    {
        sort($this->capabilities);
        sort($other->capabilities);
        return $this->capabilities === $other->capabilities;
    }
}
