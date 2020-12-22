<?php

namespace Skyroom\Menu;

use Skyroom\Adapter\WooCommerceAdapter;
use Skyroom\Tables\UsersTable;
use Skyroom\Util\Viewer;

/**
 * User submenu
 *
 * @package Skyroom\Menu
 */
class UserSubmenu extends AbstractSubmenu
{
    /**
     * @var Viewer $viewer
     */
    private $viewer;

    /**
     * Room submenu constructor
     *
     * @param Viewer         $viewer
     */
    public function __construct(Viewer $viewer, WooCommerceAdapter $wooCommerceAdapter)
    {
        $this->viewer = $viewer;
        $this->woocommerceAdaptor = $wooCommerceAdapter;

        // Set user menu attributes
        parent::__construct(
            'skyroom-users',
            __('Skyroom Users', 'skyroom'),
            __('Users', 'skyroom'),
            'manage_options'
        );
    }

    /**
     * Display users page
     */
    function display()
    {
        try {
            $users = $this->woocommerceAdaptor->getSkyroomUsers();
            $table = new UsersTable($users);
            $table->prepare_items();

            $context = [
                'table' => $table,
            ];
            $this->viewer->view('users.php', $context);

        } catch (\Exception $e) {
            $context = [
                'error' => $e->getMessage(),
            ];
            $this->viewer->view('error.php', $context);
        }
    }
}
