<?php

namespace Skyroom\Menu;

/**
 * Abstract class representing instance of submenu
 *
 * @package Skyroom\Menu
 */
abstract class AbstractSubmenu
{
    /**
     * @var string $menuSlug
     */
    public $menuSlug;

    /**
     * @var string $pageTitle
     */
    public $pageTitle;

    /**
     * @var     string $menuTitle
     */
    public $menuTitle;

    /**
     * @var string $capabilities
     */
    public $capabilities;


    /**
     * Submenu constructor.
     *
     * @param string $menuSlug
     * @param string $pageTitle
     * @param string $menuTitle
     * @param string $capabilities
     */
    public function __construct($menuSlug, $pageTitle, $menuTitle, $capabilities)
    {
        $this->menuSlug = $menuSlug;
        $this->pageTitle = $pageTitle;
        $this->menuTitle = $menuTitle;
        $this->capabilities = $capabilities;
    }

    abstract function display();
}