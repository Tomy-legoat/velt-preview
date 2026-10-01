<?php

namespace PreviewProtocol\Protocol;

class ProtocolVersion
{
    public const MAJOR = 1;
    public const MINOR = 0;
    public const PATCH = 0;

    public static function toString(): string
    {
        return sprintf('%d.%d.%d', self::MAJOR, self::MINOR, self::PATCH);
    }

    public static function fromString(string $version): self
    {
        if (preg_match('/^(0|[1-9][0-9]*)\\.(0|[1-9][0-9]*)\\.(0|[1-9][0-9]*)$/', $version, $parts) !== 1) {
            throw new \InvalidArgumentException("Invalid protocol version format: $version");
        }
        return new self((int) $parts[1], (int) $parts[2], (int) $parts[3]);
    }

    public function __construct(
        public readonly int $major,
        public readonly int $minor,
        public readonly int $patch
    ) {
        if ($major < 0 || $minor < 0 || $patch < 0) {
            throw new \InvalidArgumentException("Protocol version parts must be non-negative");
        }
    }

    public function isCompatibleWith(ProtocolVersion $other): bool
    {
        // Major version must match for compatibility
        return $this->major === $other->major;
    }

    public function isGreaterThan(ProtocolVersion $other): bool
    {
        if ($this->major !== $other->major) {
            return $this->major > $other->major;
        }
        if ($this->minor !== $other->minor) {
            return $this->minor > $other->minor;
        }
        return $this->patch > $other->patch;
    }

    public function equals(ProtocolVersion $other): bool
    {
        return $this->major === $other->major
            && $this->minor === $other->minor
            && $this->patch === $other->patch;
    }

    public function __toString(): string
    {
        return sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);
    }
}
