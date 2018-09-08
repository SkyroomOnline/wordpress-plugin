<?php

namespace Skyroom\Adapter;

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
     * @return array
     */
    function getProducts($roomIds);

    /**
     * Get singular or plural form of specific post type string
     *
     * @param bool $plural Whether to get plural or singular form
     *
     * @return string
     */
    function getPostString($plural = false);
}