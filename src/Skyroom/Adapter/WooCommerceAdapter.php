<?php

namespace Skyroom\Adapter;

use DI\Container;

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
     * @return mixed
     */
    function setup()
    {
        // TODO: Implement setup() method.

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