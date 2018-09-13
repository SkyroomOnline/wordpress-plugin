<?php

namespace Skyroom\Exception;

/**
 * Exception thrown when skyroom server response status is invalid.
 *
 * @package Skyroom\Exception
 */
class RequestFailedException extends \Exception
{
    /**
     * RequestFailedException constructor.
     *
     * @param string $message
     */
    public function __construct($code, $message)
    {
        $this->code = $code;
        $this->message = $message;

        parent::__construct($this->message, $this->code, null);
    }
}