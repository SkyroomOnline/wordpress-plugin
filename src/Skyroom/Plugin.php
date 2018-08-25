<?php

namespace Skyroom;

use DI\ContainerBuilder;
use DownShift\WordPress\EventEmitter;
use DI\Container;

/**
 * Plugin main class to build container and register wp hooks.
 *
 * @package Skyroom
 * @author  Hossein Sadeghi <ho.mysterious@gmail.com>
 */
class Plugin
{
    /**
     * @var Container
     */
    private $container;

    /**
     * Boot skyroom plugin
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function boot()
    {
        $this->container = $this->buildContainer();
        $this->container->get('Internationalization')->loadTextDomain();
        $this->registerHooks($this->container->get('Events'));
    }

    /**
     * Build container and add service and parameter definitions
     *
     * @return Container
     */
    private function buildContainer()
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(__DIR__.'/../../config.php');

        return $builder->build();
    }

    /**
     * Register plugin hooks
     *
     * @param EventEmitter $eventEmitter
     */
    private function registerHooks(EventEmitter $eventEmitter)
    {
        // Register menus
        $eventEmitter->on('admin_menu', function () {
            $menu = $this->container->get('Skyroom\Menu\MainMenu');
            $this->container->call(
                [$menu, 'register'],
                [
                    'icon' => $this->container->get('plugin.url').'admin/images/icon-32x32.png',
                ]
            );
        });

        // Enqueue assets
        $eventEmitter->on('admin_init', function () {
            $this->container->get('Skyroom\Util\AssetManager')->adminAssets();
        });
    }
}