<?php

namespace Skyroom\Adapter;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Skyroom\WooCommerce\SkyroomProductRegistrar;

/**
 * WooCommerce adapter
 *
 * @package Skyroom\Adapter
 */
class WooCommerceAdapter implements PluginAdapterInterface
{
    /**
     * @var Container $container
     */
    private $container;

    /**
     * WooCommerce Adapter constructor
     *
     * @param Container $container
     */
    function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Setup WooCommerce adapter
     *
     * @throws DependencyException
     * @throws NotFoundException
     *
     * @return mixed
     */
    function setup()
    {
        // Register custom product type
        $registrar = new SkyroomProductRegistrar($this->container);
        $registrar->register();
    }

    /**
     * Get WooCommerce posts that linked to skyroom rooms
     *
     * @param array $roomIds
     *
     * @return mixed
     */
    function getPosts($roomIds)
    {
        // TODO: Implement getPosts() method.
    }

    /**
     * Get Product singular or plural form
     *
     * @param bool $plural Whether to get plural or singular form
     *
     * @return string
     */
    function getPostString($plural = false)
    {
        return $plural ? __('Products', 'skyroom') : __('Product', 'skyroom');
    }
}