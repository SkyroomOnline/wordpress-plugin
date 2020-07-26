<?php


namespace Skyroom\Exception;

/**
 * Class InternalServerErrorException
 * @package Skyroom\Exception
 */
class InternalServerErrorException extends \Exception
{
    /**
     * InternalServerErrorException constructor.
     */
    public function __construct()
    {
        $this->code = 500;
        $this->message = __('Internal server error', 'skyroom');

        parent::__construct($this->message, $this->code, null);
    }
}