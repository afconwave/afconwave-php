<?php

namespace AfconWave\Exceptions;

class AuthException extends AfconWaveException
{
    public function __construct($message = "Invalid API Key")
    {
        parent::__construct($message, 401);
    }
}
