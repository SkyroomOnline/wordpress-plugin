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
        $this->registerHooks($this->container->get('EventEmitter'));
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

    }
}