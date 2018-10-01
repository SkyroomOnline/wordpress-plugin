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
     * @inheritdoc
     */
    public function get_type()
    {
        return 'skyroom';
    }

    /**
     * @inheritdoc
     */
    public function get_sold_individually($context = 'view')
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function get_virtual($context = 'view')
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function single_add_to_cart_text()
    {
        return apply_filters('skyroom_product_single_add_to_cart_text', parent::single_add_to_cart_text(), $this);
    }

    /**
     * Get counterpart skyroom id
     *
     * @return mixed
     */
    public function get_skyroom_id()
    {
        return get_post_meta($this->get_id(), '_skyroom_id', true);
    }

    public function get_room_title()
    {
        return get_post_meta($this->get_id(), '_skyroom_title', true);
    }
}
