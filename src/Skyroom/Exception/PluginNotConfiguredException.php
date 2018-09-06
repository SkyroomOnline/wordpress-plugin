<?php
/**
 * Exception to be thrown when plugin is not configured yet
 *
 * @link        https://skyroom.ir
 * @since       1.0.0
 * @package     Skyroom\Exception
 */

namespace Skyroom\Exception;


class PluginNotConfiguredException extends \Exception
{
    protected $message = "Plugin is not configured yet";
}