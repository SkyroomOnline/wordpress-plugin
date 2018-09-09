<?php

namespace Skyroom\Menu;

use Skyroom\Exception\ConnectionTimeoutException;
use Skyroom\Exception\InvalidResponseException;
use Skyroom\Repository\UserRepository;
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
     * @var UserRepository $repository
     */
    private $repository;

    /**
     * @var Viewer $viewer
     */
    private $viewer;

    /**
     * Room submenu constructor
     *
     * @param UserRepository $repository
     * @param Viewer         $viewer
     */
    public function __construct(UserRepository $repository, Viewer $viewer)
    {
        $this->repository = $repository;
        $this->viewer = $viewer;

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
        try {
            $users = $this->repository->getUsers();
            $table = new UsersTable($users);
            $table->prepare_items();

            $context = [
                'table' => $table,
            ];
            $this->viewer->view('users.php', $context);

        } catch (ConnectionTimeoutException $e) {
        } catch (InvalidResponseException $e) {
        }
    }
}