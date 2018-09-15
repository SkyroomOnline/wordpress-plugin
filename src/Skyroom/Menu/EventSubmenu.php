<?php

namespace Skyroom\Menu;

use Skyroom\Tables\EventsTable;
use Skyroom\Util\Viewer;

/**
 * Event submenu
 *
 * @package Skyroom\Menu
 */
class EventSubmenu extends AbstractSubmenu
{
    /**
     * @var EventsTable $repository
     */
    private $eventsTable;

    /**
     * @var Viewer $viewer
     */
    private $viewer;

    /**
     * Event submenu constructor
     *
     * @param EventsTable $eventsTable
     * @param Viewer      $viewer
     */
    public function __construct(EventsTable $eventsTable, Viewer $viewer)
    {
        $this->eventsTable = $eventsTable;
        $this->viewer = $viewer;

        // Set room menu attributes
        parent::__construct(
            'skyroom-events',
            __('Skyroom Events', 'skyroom'),
            __('Events', 'skyroom'),
            'manage_options'
        );
    }

    /**
     * Display events page
     */
    function display()
    {
        $this->eventsTable->prepare_items();

        $context = [
            'table' => $this->eventsTable,
        ];
        $this->viewer->view('events.php', $context);
    }
}