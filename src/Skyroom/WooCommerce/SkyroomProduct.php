<?php

namespace Skyroom\WooCommerce;

/**
 * WooCommerce skyroom product type
 *
 * @package Skyroom\WooCommerce
 */
class SkyroomProduct extends \WC_Product
{
    /**
     * Get product type
     *
     * @return string
     */
    public function get_type()
    {
        return 'skyroom';
    }
}