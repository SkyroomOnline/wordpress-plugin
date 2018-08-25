<?php
/**
 * Skyroom service and parameter configurations
 *
 * returns associative array of values
 */

$services = [
    'EventEmitter' => new \DownShift\WordPress\EventEmitter(),
    'Internationalization' => \DI\object('Skyroom\Util\Internationalization')
        ->constructor(\DI\get('name'), \DI\get('plugin.languagePath')),
];

$parameters = [
    'name' => 'skyroom',
    'version' => '1.0.0',
    'plugin.path' => plugin_dir_path(__FILE__),
    'plugin.url' => plugin_dir_url(__FILE__),
    'plugin.languagePath' => plugin_dir_path(__FILE__).'languages',
];

return $services + $parameters;

