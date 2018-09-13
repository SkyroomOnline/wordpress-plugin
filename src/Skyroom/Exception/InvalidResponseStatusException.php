<?php

namespace Skyroom\Exception;

/**
 * Exception thrown when skyroom server response status is invalid.
 *
 * @package Skyroom\Exception
 */
class InvalidResponseStatusException extends \Exception
{
    /**
     * InvalidResponseStatusException constructor.
     */
    public function __construct()
    {
        $this->code = 2;
        $this->message = __('Skyroom server response status is not valid', 'skyroom');

        parent::__construct($this->message, $this->code, null);
    }
}