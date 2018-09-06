<?php

namespace Skyroom\Util;

/**
 * Plugin internationalization stuff
 *
 * @package Skyroom\Utils
 * @author  Hossein Sadeghi <ho.mysterious@gmail.com>
 */
class Internationalization
{
    /**
     * @var string $domain Plugin text domain
     */
    private $domain;

    /**
     * @var string $path Path to languages directory
     */
    private $path;

    /**
     * Internationalization constructor
     *
     * @param $domain
     */
    public function __construct($domain, $path)
    {
        $this->domain = $domain;
        $this->path = $path;
    }

    /**
     * Load plugin text domain using wp functions
     */
    public function loadTextDomain()
    {
        load_plugin_textdomain(
            $this->domain,
            false,
            $this->path
        );
    }
}