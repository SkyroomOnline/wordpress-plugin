<?php

namespace Skyroom\Entity;

/**
 * Enrollment entity
 *
 * @package Skyroom\Entity
 */
class Enrollment
{
    /**
     * @var ProductWrapperInterface $product
     */
    private $product;

    /**
     * @var int
     */
    private $enrollTime;

    /**
     * Event constructor.
     *
     * @param ProductWrapperInterface $product
     * @param int $enrollTime
     */
    public function __construct($product, $enrollTime)
    {
        $this->product = $product;
        $this->enrollTime = $enrollTime;
    }

    /**
     * @return ProductWrapperInterface
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return int
     */
    public function getEnrollTime()
    {
        return $this->enrollTime;
    }
}