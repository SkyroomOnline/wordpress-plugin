<?php

namespace Skyroom\Controller;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Skyroom\Tasks\SyncTask;
use WP_Error;

class SyncTaskController
{
    const startActionIdentifier = 'skyroom_sync_start';
    const statusActionIdentifier = 'skyroom_sync_status';

    private $syncTask;

    /**
     * SyncTaskController constructor.
     * @param SyncTask $syncTask
     */
    public function __construct(SyncTask $syncTask)
    {
        $this->syncTask = $syncTask;
    }

    /**
     * Start sync task
     */
    public function startSyncTask()
    {
        check_ajax_referer(SyncTaskController::startActionIdentifier, 'nonce');
        $result = $this->syncTask->initTask()->save()->dispatch();
        if ($result instanceof WP_Error) {
            wp_send_json_error(__('Something bad happened while trying to start synchronization', 'skyroom'));
        } else {
            wp_send_json_success();
        }
    }

    /**
     * Get current status of sync task
     */
    public function getSyncStatus()
    {
        check_ajax_referer(SyncTaskController::statusActionIdentifier, 'nonce');
        wp_send_json(get_transient('skyroom_sync_data_status'));
    }
}
