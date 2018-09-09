<?php

namespace Skyroom\Entity;

use Skyroom\WooCommerce\SkyroomProduct;

/**
 * WooCommerce product wrapper
 *
 * @package Skyroom\Entity
 */
class WooCommerceProductWrapper implements ProductWrapperInterface
{
    /**
     * @var SkyroomProduct
     */
    private $product;

    /**
     * WooCommerceProduct constructor.
     *
     * @param $product
     */
    public function __construct($product)
    {
        $this->product = $product;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->product->get_id();
    }

    /**
     * @inheritdoc
     */
    public function getSkyroomId()
    {
        return $this->product->get_skyroom_id();
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->product->get_title();
    }

    /**
     * @inheritdoc
     */
    public function getPermalink()
    {
        return $this->product->get_permalink();
    }
}