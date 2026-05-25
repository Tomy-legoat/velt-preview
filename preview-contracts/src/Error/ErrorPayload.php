<?php

namespace PreviewContracts\Error;

class ErrorPayload
{
    public string $code;
    public string $message;
    public ?string $detail;

    public function __construct(string $code, string $message, ?string $detail = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->detail = $detail;
    }

    public function toArray(): array
    {
        $err = [
            'code' => $this->code,
            'message' => $this->message,
        ];
        if ($this->detail !== null) {
            $err['detail'] = $this->detail;
        }
        return ['error' => $err];
    }
}
