<?php
/**
 * Skyroom service and parameter configurations
 *
 * returns associative array of values
 */

$services = [
    'EventEmitter' => new \DownShift\WordPress\EventEmitter(),
];

$parameters = [
    'name' => 'skyroom',
    'version' => '1.0.0',
    'plugin.path' => plugin_dir_path(__FILE__),
    'plugin.url' => plugin_dir_url(__FILE__),
];

return $services + $parameters;

