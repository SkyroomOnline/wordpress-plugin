<?php

namespace Skyroom\Menu;

use Skyroom\Util\Viewer;

/**
 * Sync submenu
 *
 * @package Skyroom\Menu
 */
class SyncSubmenu extends AbstractSubmenu
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
            'skyroom-sync',
            __('Skyroom Synchronization', 'skyroom'),
            __('Synchronize', 'skyroom'),
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
        $this->viewer->view('sync.php', $context);
    }
}