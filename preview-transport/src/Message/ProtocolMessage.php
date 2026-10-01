<?php

namespace PreviewTransport\Message;

class ProtocolMessage
{
    public function __construct(
        public readonly string $type,
        public readonly int $sequence,
        public readonly array $payload,
        public readonly ?string $sessionId = null,
        public readonly ?int $timestamp = null
    ) {
        if (!in_array($type, MessageType::cases(), true)) {
            throw new \InvalidArgumentException("Invalid message type: $type");
        }
        if ($sequence < 0) {
            throw new \InvalidArgumentException('Sequence must be non-negative');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['type'] ?? '',
            $data['sequence'] ?? 0,
            $data['payload'] ?? [],
            $data['sessionId'] ?? null,
            $data['timestamp'] ?? null
        );
    }

    public function toArray(): array
    {
        $message = [
            'type' => $this->type,
            'sequence' => $this->sequence,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp ?? time(),
        ];

        if ($this->sessionId !== null) {
            $message['sessionId'] = $this->sessionId;
        }

        return $message;
    }

    public static function createSessionInit(string $sessionId, array $capabilities, int $sequence): self
    {
        return new self(
            MessageType::SESSION_INIT,
            $sequence,
            [
                'sessionId' => $sessionId,
                'capabilities' => $capabilities,
            ],
            $sessionId
        );
    }

    public static function createUiSnapshot(array $uiTree, int $sequence, ?string $sessionId = null): self
    {
        return new self(
            MessageType::UI_SNAPSHOT,
            $sequence,
            ['ui' => $uiTree],
            $sessionId
        );
    }

    public static function createUiDiff(array $diff, int $sequence, ?string $sessionId = null): self
    {
        return new self(
            MessageType::UI_DIFF,
            $sequence,
            ['diff' => $diff],
            $sessionId
        );
    }

    public static function createEvent(string $eventId, array $eventData, int $sequence, ?string $sessionId = null): self
    {
        return new self(
            MessageType::EVENT,
            $sequence,
            [
                'eventId' => $eventId,
                'data' => $eventData,
            ],
            $sessionId
        );
    }

    public static function createHeartbeat(int $sequence, ?string $sessionId = null): self
    {
        return new self(
            MessageType::HEARTBEAT,
            $sequence,
            [],
            $sessionId
        );
    }

    public static function createError(string $code, string $message, int $sequence, ?string $sessionId = null): self
    {
        return new self(
            MessageType::ERROR,
            $sequence,
            [
                'code' => $code,
                'message' => $message,
            ],
            $sessionId
        );
    }
}
