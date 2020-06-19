<?php

namespace Skyroom\Menu;

use Skyroom\Util\Viewer;

/**
 * Sync submenu
 *
 * @package Skyroom\Menu
 */
class MaintenanceSubmenu extends AbstractSubmenu
{
    /**
     * @var Viewer $viewer
     */
    private $viewer;

    /**
     * Room submenu constructor
     *
     * @param Viewer $viewer
     */
    public function __construct(Viewer $viewer)
    {
        $this->viewer = $viewer;

        // Set user menu attributes
        parent::__construct(
            'skyroom-maintenance',
            __('Skyroom Maintenance', 'skyroom'),
            __('Maintenance', 'skyroom'),
            'manage_options'
        );
    }

    /**
     * Display synchronize page
     */
    function display()
    {
        // View page template
        $context = [
            'lastSync' => get_option('skyroom_last_sync'),
        ];
        $this->viewer->view('maintenance.php', $context);
    }
}
