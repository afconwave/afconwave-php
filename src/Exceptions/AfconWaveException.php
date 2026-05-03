<?php

namespace AfconWave\Exceptions;

class AfconWaveException extends \Exception
{
    protected $statusCode;

    public function __construct($message = "", $statusCode = 0, \Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
