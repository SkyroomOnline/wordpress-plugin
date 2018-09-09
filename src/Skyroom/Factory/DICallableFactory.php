<?php

namespace Skyroom\Factory;

use DI\Container;
use Skyroom\Util\DICallable;

/**
 * DICallable factory
 *
 * @package Skyroom\Factory
 */
class DICallableFactory
{
    /**
     * @var Container Container reference
     */
    private $container;

    /**
     * DICallable factory constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Create DICallable instance
     *
     * @param callable $callable
     *
     * @return DICallable
     */
    public function create(callable $callable)
    {
        return new DICallable($this->container, $callable);
    }
}