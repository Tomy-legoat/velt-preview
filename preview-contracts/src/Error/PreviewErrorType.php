<?php

namespace PreviewContracts\Error;

class PreviewErrorType
{
    public const SESSION_NOT_FOUND = 'SESSION_NOT_FOUND';
    public const SESSION_EXPIRED = 'SESSION_EXPIRED';
    public const PAGE_NOT_FOUND = 'PAGE_NOT_FOUND';
    public const INTERNAL_ERROR = 'INTERNAL_ERROR';
    public const INVALID_SIGNATURE = 'INVALID_SIGNATURE';
    public const INVALID_NONCE = 'INVALID_NONCE';
    public const INVALID_ORIGIN = 'INVALID_ORIGIN';
    public const INVALID_HOST = 'INVALID_HOST';
    public const PAYLOAD_TOO_LARGE = 'PAYLOAD_TOO_LARGE';
    public const INVALID_PAYLOAD = 'INVALID_PAYLOAD';
    public const PROTOCOL_MISMATCH = 'PROTOCOL_MISMATCH';
    public const CAPABILITY_NEGOTIATION_FAILED = 'CAPABILITY_NEGOTIATION_FAILED';
    public const REPLAY_ATTACK = 'REPLAY_ATTACK';
    public const SESSION_REVOKED = 'SESSION_REVOKED';
}
