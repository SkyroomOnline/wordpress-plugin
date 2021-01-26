<?php

namespace Skyroom\Menu;

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
     * @var UsersTable $usersTable
     */
    private $usersTable;


    /**
     * @var Viewer $viewer
     */
    private $viewer;

    /**
     * Room submenu constructor
     *
     * @param UsersTable $usersTable
     * @param Viewer $viewer
     */
    public function __construct(UsersTable $usersTable, Viewer $viewer)
    {
        $this->viewer = $viewer;
        $this->usersTable = $usersTable;

        // Set user menu attributes
        parent::__construct(
            'skyroom-users',
            __('Users Registered', 'skyroom'),
            __('Registered', 'skyroom'),
            'manage_options'
        );
    }

    /**
     * Display users page
     */
    function display()
    {
        $this->usersTable->prepare_items();
        $context = [
            'table' => $this->usersTable,
        ];
        $this->viewer->view('users.php', $context);
    }
}
