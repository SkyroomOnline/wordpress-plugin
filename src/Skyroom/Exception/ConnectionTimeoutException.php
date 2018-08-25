<?php
/**
 * Exception to be thrown when there's connection problem
 *
 * @link        https://skyroom.ir
 * @since       1.0.0
 * @package     Skyroom\Exception
 */

namespace Skyroom\Exception;


class ConnectionTimeoutException extends \Exception
{
    protected $message = "Can't connect to webservice";
}