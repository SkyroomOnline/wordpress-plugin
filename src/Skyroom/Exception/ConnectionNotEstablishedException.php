<?php

namespace Skyroom\Exception;

/**
 * Exception thrown when connection to skyroom server can't be established.
 *
 * @package Skyroom\Exception
 */
class ConnectionNotEstablishedException extends \Exception
{
    /**
     * ConnectionTimeoutException constructor.
     */
    public function __construct()
    {
        $this->code = 1;
        $this->message = __('Connection to skyroom server could not be established', 'skyroom');

        parent::__construct($this->message, $this->code, null);
    }
}