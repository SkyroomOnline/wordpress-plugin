<?php


namespace Skyroom\Exception;

/**
 * Class NotFoundErrorException
 * @package Skyroom\Exception
 */
class NotFoundErrorException extends \Exception
{
    /**
     * NotFoundErrorException constructor.
     */
    public function __construct()
    {
        $this->code = 404;
        $this->message = __('Not Found', 'skyroom');

        parent::__construct($this->message, $this->code, null);
    }
}