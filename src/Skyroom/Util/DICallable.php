<?php

namespace Skyroom\Util;

use DI\Container;

/**
 * Dependency Injected callable
 *
 * @package Skyroom\Util
 */
class DICallable
{
    /**
     * @var Container $container
     */
    private $container;

    /**
     * @var Callable $callable
     */
    private $callable;

    /**
     * DICallable constructor.
     *
     * @param Container $container Container object
     * @param Callable  $callable  Function to call
     */
    public function __construct(Container $container, $callable)
    {
        $this->container = $container;
        $this->callable = $callable;
    }

    public function __invoke(...$params)
    {
        try {
            $reflection = is_array($this->callable)
                ? new \ReflectionMethod($this->callable[0], $this->callable[1])
                : new \ReflectionFunction($this->callable);

            $parameters = [];
            $params = $reflection->getParameters();
            $args = func_get_args();
            for ($i = 0; $i < func_num_args(); $i++) {
                $name = $params[$i]->name;
                $parameters[$name] = $args[$i];
            }

            return $this->container->call($this->callable, $parameters);

        } catch (\ReflectionException $e) {
        }
    }
}