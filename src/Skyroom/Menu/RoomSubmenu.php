<?php

namespace Skyroom\Menu;

use Skyroom\Repository\RoomRepository;

/**
 * Room submenu
 *
 * @package Skyroom\Menu
 */
class RoomSubmenu extends AbstractSubmenu
{
    /**
     * @var RoomRepository $repository
     */
    private $repository;

    /**
     * Room submenu constructor
     *
     * @param RoomRepository $repository
     */
    public function __construct(RoomRepository $repository)
    {
        $this->repository = $repository;

        // Set room menu attributes
        parent::__construct(
            'skyroom-rooms',
            __('Skyroom Rooms', 'skyroom'),
            __('Rooms', 'skyroom'),
            'manage_options'
        );
    }

    /**
     * Display rooms page
     */
    function display()
    {
        // TODO display rooms
    }
}