<?php

namespace Skyroom\Entity;

/**
 * Product interface
 *
 * @package Skyroom\Entity
 */
interface ProductWrapperInterface
{
    /**
     * Get product ID
     *
     * @return integer
     */
    public function getId();

    /**
     * Get product title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Get product permalink
     *
     * @return string
     */
    public function getPermalink();
}