<?php
/**
 * Exception to be thrown when http response is not valid
 *
 * @link        https://skyroom.ir
 * @since       1.0.0
 * @package     Skyroom\Exception
 */

namespace Skyroom\Exception;

use Throwable;

class InvalidResponseException extends \Exception
{
    const INVALID_RESPONSE_STATUS = 1;
    const INVALID_RESPONSE_CONTENT = 2;
    const INVALID_RESULT = 3;

    public function __construct($code = self::INVALID_RESPONSE_STATUS, Throwable $previous = null)
    {
        switch ($code) {
            case self::INVALID_RESPONSE_STATUS:
                $message = 'Invalid response status';
                break;

            case self::INVALID_RESPONSE_CONTENT:
                $message = 'Malformed response content';
                break;

            case self::INVALID_RESULT:
                $message = 'Webservice result is not ok';
                break;

            default:
                $message = '';
                break;
        }

        parent::__construct($message, $code, $previous);
    }
}