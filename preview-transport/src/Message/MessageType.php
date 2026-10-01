<?php

namespace PreviewTransport\Message;

class MessageType
{
    public const SESSION_INIT = 'session_init';
    public const SESSION_ACK = 'session_ack';
    public const UI_SNAPSHOT = 'ui_snapshot';
    public const UI_DIFF = 'ui_diff';
    public const EVENT = 'event';
    public const HEARTBEAT = 'heartbeat';
    public const ERROR = 'error';
    public const CAPABILITY_NEGOTIATION = 'capability_negotiation';
    public const SESSION_RESUME = 'session_resume';

    public static function cases(): array
    {
        return [
            self::SESSION_INIT,
            self::SESSION_ACK,
            self::UI_SNAPSHOT,
            self::UI_DIFF,
            self::EVENT,
            self::HEARTBEAT,
            self::ERROR,
            self::CAPABILITY_NEGOTIATION,
            self::SESSION_RESUME,
        ];
    }
}
