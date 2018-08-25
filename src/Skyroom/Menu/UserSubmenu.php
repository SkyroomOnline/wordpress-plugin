<?php

namespace Skyroom\Menu;

use Skyroom\Repository\UserRepository;

/**
 * User submenu
 *
 * @package Skyroom\Menu
 */
class UserSubmenu extends AbstractSubmenu
{
    /**
     * @var UserRepository $repository
     */
    private $repository;

    /**
     * Room submenu constructor
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;

        // Set user menu attributes
        parent::__construct(
            'skyroom-users',
            __('Skyroom Users',
                'skyroom'),
            __('Users'),
            'manage_options'
        );
    }

    /**
     * Display users page
     */
    function display()
    {
        // TODO display users
    }
}