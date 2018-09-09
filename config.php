<?php
/**
 * Skyroom service and parameter configurations
 *
 * returns associative array of values
 */

$services = [
    'DownShift\WordPress\EventEmitter' => \DI\object('DownShift\WordPress\EventEmitter'),
    'Internationalization' => \DI\object('Skyroom\Util\Internationalization')
        ->constructor(\DI\get('name'), \DI\get('plugin.languagePath')),
    'Skyroom\Api\URL' => \DI\object('Skyroom\Api\URL')
        ->constructor(\DI\get('setting.site'), \DI\get('setting.key')),
    'Skyroom\Util\AssetManager' => \DI\object('Skyroom\Util\AssetManager')
        ->constructor(\DI\get('plugin.url'), \DI\get('version')),
    'Skyroom\Util\Viewer' => \DI\object('Skyroom\Util\Viewer')
        ->constructor(\DI\get('plugin.path')),
    'Skyroom\Factory\DICallableFactory' => \DI\object('Skyroom\Factory\DICallableFactory'),
    'Skyroom\Adapter\PluginAdapterInterface' => function (\DI\Container $container) {
        switch ($container->get('setting.plugin')) {
            case 'woocommerce':
                return new Skyroom\Adapter\WooCommerceAdapter($container);

            default:
                return null;
        }
    },

    // Aliases
    'Events' => \DI\get('DownShift\WordPress\EventEmitter'),
    'Viewer' => \DI\get('Skyroom\Util\Viewer'),
    'PluginAdapter' => \DI\get('Skyroom\Adapter\PluginAdapterInterface'),
    'DICallableFactory' => \DI\get('Skyroom\Factory\DICallableFactory'),
];

$parameters = [
    'name' => 'skyroom',
    'version' => '1.0.0',
    'plugin.path' => plugin_dir_path(__FILE__),
    'plugin.url' => plugin_dir_url(__FILE__),
    'plugin.languagePath' => plugin_dir_path(__FILE__).'languages',
    'setting.site' => get_option('skyroom_site_url'),
    'setting.key' => get_option('skyroom_api_key'),
    'setting.plugin' => get_option('skyroom_integrated_plugin'),
];

return $services + $parameters;
