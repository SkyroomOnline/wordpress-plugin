<?php

namespace Skyroom\Controller;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Tasks\SyncTask;
use WP_Error;

class MaintenanceController
{
    const startActionIdentifier = 'skyroom_sync_start';
    const statusActionIdentifier = 'skyroom_sync_status';
    const purgeActionIdentifier = 'skyroom_purge_data';

    /**
     * @var SyncTask $syncTask
     */
    private $syncTask;

    /**
     * @var PluginAdapterInterface $pluginAdapter
     */
    private $pluginAdapter;

    /**
     * SyncTaskController constructor.
     *
     * @param SyncTask $syncTask
     * @param PluginAdapterInterface $pluginAdapter
     */
    public function __construct(SyncTask $syncTask, PluginAdapterInterface $pluginAdapter)
    {
        $this->syncTask = $syncTask;
        $this->pluginAdapter = $pluginAdapter;
    }

    /**
     * Start sync task
     */
    public function startSyncTask()
    {
        check_ajax_referer(MaintenanceController::startActionIdentifier, 'nonce');
        $result = $this->syncTask->initTask()->save()->dispatch();
        if ($result instanceof WP_Error) {
            wp_send_json_error(__('Something bad happened while trying to start synchronization', 'skyroom'));
        } else {
            wp_send_json_success(get_transient('skyroom_sync_data_status'));
        }
    }

    /**
     * Get current status of sync task
     */
    public function getSyncStatus()
    {
        check_ajax_referer(MaintenanceController::statusActionIdentifier, 'nonce');
        wp_send_json(get_transient('skyroom_sync_data_status'));
    }

    /**
     * Purge data
     */
    public function purgeData()
    {
        check_ajax_referer(MaintenanceController::purgeActionIdentifier, 'nonce');
        try {
            $this->pluginAdapter->purgeData();
            wp_send_json_success();
        } catch (\Exception $exception) {
            wp_send_json_error();
        }
    }
}
