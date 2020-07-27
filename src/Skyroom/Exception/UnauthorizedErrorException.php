<?php


namespace Skyroom\Exception;

/**
 * Class UnauthorizedErrorException
 * @package Skyroom\Exception
 */
class UnauthorizedErrorException extends \Exception
{
    /**
     * UnauthorizedErrorException constructor.
     */
    public function __construct()
    {
        $this->code = 401;
        $this->message = __('Unauthorized token api', 'skyroom');

        parent::__construct($this->message, $this->code, null);
    }
}