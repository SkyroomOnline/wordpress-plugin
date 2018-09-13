<?php

namespace Skyroom\Menu;

use Skyroom\Repository\RoomRepository;
use Skyroom\Tables\RoomsTable;
use Skyroom\Util\Viewer;

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
     * @var Viewer $viewer
     */
    private $viewer;

    /**
     * Room submenu constructor
     *
     * @param RoomRepository $repository
     * @param Viewer         $viewer
     */
    public function __construct(RoomRepository $repository, Viewer $viewer)
    {
        $this->repository = $repository;
        $this->viewer = $viewer;

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
        try {
            $rooms = $this->repository->getRooms();
            $table = new RoomsTable($rooms, $this->repository->getPostString());
            $table->prepare_items();

            $context = [
                'table' => $table,
            ];
            $this->viewer->view('rooms.php', $context);

        } catch (\Exception $e) {
            $context = [
                'error' => $e->getMessage(),
            ];
            $this->viewer->view('error.php', $context);
        }
    }
}