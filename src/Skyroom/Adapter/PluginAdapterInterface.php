<?php

namespace Skyroom\Adapter;

use Skyroom\Entity\ProductWrapperInterface;

/**
 * Plugin adapter interface
 *
 * @package Skyroom\Adapter
 */
interface PluginAdapterInterface
{
    /**
     * Setup plugin adapter
     *
     * @return mixed
     */
    function setup();

    /**
     * Get plugin specific products that linked to skyroom rooms
     *
     * @param array $roomIds
     *
     * @return ProductWrapperInterface[]
     */
    function getProducts($roomIds);

    /**
     * Get purchases that are not saved on enrolls table
     */
    function getUntrackedPurchases();

    /**
     * Get singular or plural form of specific post type string
     *
     * @param bool $plural Whether to get plural or singular form
     *
     * @return string
     */
    function getPostString($plural = false);
}