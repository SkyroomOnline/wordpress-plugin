<?php

namespace Skyroom\Util;

/**
 * Class Viewer
 *
 * @package Skyroom\Util
 */
class Viewer
{
    /**
     * @var string
     */
    private $pluginPath;

    /**
     * @var string
     */
    private $pluginUrl;

    /**
     * Viewer constructor.
     *
     * @param string $pluginPath
     * @param string $pluginUrl
     */
    public function __construct($pluginPath, $pluginUrl)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginUrl = $pluginUrl;
    }

    /**
     * Include view file
     *
     * @param string $fileName View file
     * @param array  $context
     */
    public function view($fileName, $context = [])
    {
        extract($context);
        include $this->pluginPath.'views/'.$fileName;
    }
}