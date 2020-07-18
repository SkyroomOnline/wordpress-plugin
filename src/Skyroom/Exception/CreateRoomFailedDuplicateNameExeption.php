<?php


namespace Skyroom\Exception;


/**
 * room is already exists error
 * @package Skyroom\Exception
 */
class CreateRoomFailedDuplicateNameExeption extends \Exception
{
    /**
     * CreateRoomFailedDuplicateNameExeption constructor.
     */
    public function __construct()
    {
        $this->code = 8;
        $this->message = __('This room already exists', 'skyroom');

        parent::__construct($this->message, $this->code, null);
    }
}