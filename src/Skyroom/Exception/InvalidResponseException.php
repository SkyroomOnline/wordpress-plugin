<?php

namespace Skyroom\Exception;

/**
 * Exception thrown when skyroom server response doesn't have valid format.
 *
 * @package Skyroom\Exception
 */
class InvalidResponseException extends \Exception
{
    /**
     * InvalidResponseException constructor.
     */
    public function __construct()
    {
        $this->code = 3;
        $this->message = __('Skyroom server response has invalid format', 'skyroom');

        parent::__construct($this->message, $this->code, null);
    }
}