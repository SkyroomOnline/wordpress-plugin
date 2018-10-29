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
        wp_enqueue_script('skyroom', $this->pluginUrl.'admin/js/script.js', $this->version);

        wp_enqueue_script('skyroom-alertifyjs', $this->pluginUrl.'admin/js/alertify.min.js', $this->version);
        if (!is_rtl()) {
            wp_enqueue_style('skyroom-alertifyjs', $this->pluginUrl.'admin/css/alertify.min.css', $this->version);
        } else {
            wp_enqueue_style('skyroom-alertifyjs', $this->pluginUrl.'admin/css/alertify.rtl.min.css', $this->version);
        }

        wp_localize_script(
            'skyroom',
            'skyroom_data',
            [
                'ok' => __('OK', 'skyroom'),
                'event_details' => __('Event details', 'skyroom'),
                'error_code' => __('Error code', 'skyroom'),
                'error_message' => __('Error message', 'skyroom'),
                'order_id' => __('Order ID', 'skyroom'),
                'item_id' => __('Item ID', 'skyroom'),
                'user_id' => __('User ID', 'skyroom'),
                'room_id' => __('Room ID', 'skyroom'),
                'skyroom_sync_nonce' => wp_create_nonce('wp_skyroom_sync_data'),
            ]
        );
    }
}