<?php

namespace Skyroom\Util;

/**
 * Contains hooks for enqueuing assets to wordpress
 *
 * @package Skyroom\Util
 */
class AssetManager
{
    /**
     * @var string $pluginUrl
     */
    private $pluginUrl;

    /**
     * @var string $version
     */
    private $version;

    /**
     * AssetManager constructor.
     *
     * @param string $pluginUrl
     * @param string $version
     */
    public function __construct($pluginUrl, $version)
    {
        $this->pluginUrl = $pluginUrl;
        $this->version = $version;
    }

    /**
     * Enqueue assets of wordpress public side
     *
     * @param string $hook Indicates current page type
     */
    public function publicAssets($hook)
    {
        // Add public script and styles
    }

    /**
     * Enqueue assets of wordpress admin side
     */
    public function adminAssets()
    {
        wp_enqueue_style('skyroom', $this->pluginUrl.'admin/css/style.css', $this->version);
    }
}