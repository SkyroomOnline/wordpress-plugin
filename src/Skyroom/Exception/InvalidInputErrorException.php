<?php


namespace Skyroom\Exception;

/**
 * Class InvalidInputErrorException
 * @package Skyroom\Exception
 */
class InvalidInputErrorException extends \Exception
{
    /**
     * InvalidInputErrorException constructor.
     */
    public function __construct()
    {
        $this->code = 400;
        $this->message = __('Invalid input', 'skyroom');

        parent::__construct($this->message, $this->code, null);
    }
}