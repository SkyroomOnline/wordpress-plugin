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
    'Skyroom\Api\URLManager' => \DI\object('Skyroom\Api\URLManager')
        ->constructor(\DI\get('webservice.site'), \DI\get('webservice.key')),
];

$parameters = [
    'name' => 'skyroom',
    'version' => '1.0.0',
    'plugin.path' => plugin_dir_path(__FILE__),
    'plugin.url' => plugin_dir_url(__FILE__),
    'plugin.languagePath' => plugin_dir_path(__FILE__).'languages',
    'webservice.site' => get_option('skyroom_site_url'),
    'webservice.key' => get_option('skyroom_api_key'),
];

return $services + $parameters;

