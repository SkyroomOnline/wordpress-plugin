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
     * Viewer constructor.
     *
     * @param string $pluginPath
     */
    public function __construct($pluginPath)
    {
        $this->pluginPath = $pluginPath;
    }

    /**
     * Include view file
     *
     * @param string $fileName View file
     */
    public function view($fileName)
    {
        include $this->pluginPath.'views/'.$fileName;
    }
}