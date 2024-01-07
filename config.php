<?php
/**
 * Skyroom service and parameter configurations
 *
 * returns associative array of values
 */

use DownShift\WordPress\EventEmitter;
use DownShift\WordPress\EventEmitterInterface;
use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Adapter\WooCommerceAdapter;
use Skyroom\Api\URL;
use Skyroom\Util\AssetManager;
use Skyroom\Util\Internationalization;
use Skyroom\Util\Viewer;

$services = [
    wpdb::class => $GLOBALS['wpdb'],
    EventEmitterInterface::class => DI\object(EventEmitter::class),
    Internationalization::class => DI\object()
        ->constructor(DI\get('name'), DI\get('plugin.path.languages')),
    URL::class => DI\object()
        ->constructor(DI\get('setting.site'), DI\get('setting.key')),
    AssetManager::class => DI\object()
        ->constructor(DI\get('plugin.url'), DI\get('version')),
    Viewer::class => DI\object()
        ->constructor(DI\get('plugin.path'), DI\get('plugin.url')),
    PluginAdapterInterface::class => function (DI\Container $container) {
        switch ($container->get('setting.plugin')) {
            case 'woocommerce':
                return $container->get(WooCommerceAdapter::class);

            default:
                return null;
        }
    },
];

$parameters = [
    'name' => 'skyroom',
    'version' => '1.6.3',
    'plugin.path' => plugin_dir_path(__FILE__),
    'plugin.url' => plugin_dir_url(__FILE__),
    'plugin.path.languages' => 'skyroom/languages',
    'setting.site' => get_option('skyroom_site_url'),
    'setting.key' => get_option('skyroom_api_key'),
    'setting.plugin' => get_option('skyroom_integrated_plugin'),
];

return $services + $parameters;
