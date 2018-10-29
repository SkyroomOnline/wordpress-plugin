<?php

namespace Skyroom\Menu;

/**
 * Skyroom plugin main menu
 *
 * @package Skyroom\Menu
 */
class MainMenu
{

    /**
     * Register main menu and submenus
     */
    public function register()
    {
        // Get submenu args
        $submenus = func_get_args();

        // Set slug same as first submenu slug
        $slug = $submenus[0]->menuSlug;

        add_menu_page(
            __('Skyroom Integration', 'skyroom'),
            __('Skyroom', 'skyroom'),
            'manage_options',
            $slug,
            '',
            '',
            48
        );

        foreach ($submenus as $submenu) {
            add_submenu_page(
                $slug,
                $submenu->pageTitle,
                $submenu->menuTitle,
                $submenu->capabilities,
                $submenu->menuSlug,
                [$submenu, 'display']
            );
        }
    }
}