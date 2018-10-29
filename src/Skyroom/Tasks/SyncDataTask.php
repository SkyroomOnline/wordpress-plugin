<?php

namespace Skyroom\Tasks;

use DI\Container;
use SkyroomLibs\WPBackgroundProcess;

/**
 * Background processing task to sync data with servers. Lazily uses ActualSyncDataTask behind the scenes.
 *
 * @package Skyroom\Tasks
 */
class SyncDataTask extends WPBackgroundProcess
{
    protected $action = 'skyroom_sync_data';

    private $container;

    /**
     * SyncDataTask constructor. calls ActualSyncDataTask for actual task processing.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct();

        $this->container = $container;

        add_action('wp_ajax_skyroom_sync_start', [$this, 'startSyncTask']);
        add_action('wp_ajax_skyroom_sync_status', [$this, 'getSyncStatus']);
    }

    public function startSyncTask()
    {
        check_ajax_referer($this->identifier, 'nonce');

        // Create status transient
        set_transient('skyroom_sync_data_status', ['status' => 'busy']);

        // Push first task to queue
        $this->push_to_queue(0);

        // Save and dispatch background process
        $result = $this->save()->dispatch();

        if ($result instanceof \WP_Error) {
            wp_send_json_error(__('Something bad happened while trying to start synchronization', 'skyroom'));
        } else {
            wp_send_json_success();
        }
    }

    public function getSyncStatus()
    {
        check_ajax_referer($this->identifier, 'nonce');

        wp_send_json(get_transient('skyroom_sync_data_status'));
    }

    /**
     * Run ActualSyncDataTask task
     *
     * @param int $item Task index in array
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     *
     * @return mixed
     */
    protected function task($item)
    {
        return $this->container->get(ActualSyncDataTask::class)->task($item);
    }

    /**
     * Run ActualSyncDataTask task
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function complete()
    {
        parent::complete();

        $this->container->get(ActualSyncDataTask::class)->complete();
    }
}