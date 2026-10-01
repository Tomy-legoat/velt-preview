<?php

namespace PreviewTransport\Transport;

use PreviewTransport\Message\ProtocolMessage;

interface TransportLayer
{
    public function send(ProtocolMessage $message): void;
    
    public function receive(): ?ProtocolMessage;
    
    public function isConnected(): bool;
    
    public function connect(): void;
    
    public function disconnect(): void;
}
