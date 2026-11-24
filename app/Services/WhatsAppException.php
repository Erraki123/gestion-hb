<?php

namespace App\Services;

class WhatsAppException extends \RuntimeException
{
    public function __construct(string $message, public bool $retryable = false)
    {
        parent::__construct($message);
    }
}
