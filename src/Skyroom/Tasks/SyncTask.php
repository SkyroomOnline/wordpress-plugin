<?php

namespace Skyroom\Tasks;

use Ds\Set;
use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Exception\BatchOperationFailedException;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;
use Skyroom\Exception\RequestFailedException;
use Skyroom\Repository\RoomRepository;
use Skyroom\Util\Utils;
use SkyroomLibs\WPBackgroundProcess;
use wpdb;

/**
 * Class SyncTask
 *
 * @package Skyroom\Tasks
 */
class SyncTask extends WPBackgroundProcess
{
    /**
     * @var string
     */
    protected $action = 'skyroom_sync';

    private $client;
    private $roomRepository;
    private $pluginAdapter;
    private $wpdb;

    private $error = false;
    private $tasks;

    /**
     * SyncDataTaskRunner constructor. Initializes tasks.
     *
     * @param Client $client
     * @param RoomRepository $roomRepository
     * @param PluginAdapterInterface $pluginAdapter
     * @param wpdb $wpdb
     */
    public function __construct(
        Client $client,
        RoomRepository $roomRepository,
        PluginAdapterInterface $pluginAdapter,
        wpdb $wpdb
    )
    {
        parent::__construct();

        $this->client = $client;
        $this->roomRepository = $roomRepository;
        $this->pluginAdapter = $pluginAdapter;
        $this->wpdb = $wpdb;
        $this->tasks = [
            'syncRooms'
        ];
    }

    public function initTask()
    {
        // Push first task to queue
        $this->push_to_queue(0);

        // Create status transient
        set_transient('skyroom_sync_data_status', ['status' => 'busy']);

        // Add start message
        $this->addMessage(__('Start sync service', 'skyroom'), 'pending');

        return $this;
    }

    /**
     * Run individual task
     *
     * @param int $item Task index in array
     *
     * @return mixed
     */
    protected function task($item)
    {
        if (call_user_func([$this, $this->tasks[$item]])) {
            $next = $item + 1;

            return $next < count($this->tasks) ? $next : false;
        } else {
            $this->error = true;
        }

        return false;
    }

    protected function complete()
    {
        if ($this->error) {
            $this->addMessage(__('Syncing stopped due to errors', 'skyroom'), 'error');
        } else {
            $this->addMessage(__('Syncing completed successfully', 'skyroom'), 'done');
        }

        $status = get_transient('skyroom_sync_data_status');
        $status['status'] = 'completed';
        set_transient('skyroom_sync_data_status', $status);
        update_option('skyroom_last_sync', time(), false);
    }

    public function addMessage($message, $type = 'done', $replace = false)
    {
        $status = get_transient('skyroom_sync_data_status');

        if (empty($status['messages'])) {
            $index = 0;
        } else {
            $count = count($status['messages']);
            $index = $replace ? $count - 1 : $count;
        }

        $status['messages'][$index] = [
            'message' => $message,
            'type' => $type,
        ];

        set_transient('skyroom_sync_data_status', $status);
    }

    public function syncRooms()
    {
        // Set start message done
        $this->addMessage(__('Start sync service', 'skyroom'), 'done', true);

        // Add new message
        $this->addMessage(__('Finding unsynced products...', 'skyroom'), 'pending');

        // Get purchases that are not saved on skyroom
        $products = $this->pluginAdapter->getProducts();

        $rooms = [];
        try {
            $rooms = $this->roomRepository->getRooms();
        } catch (\Exception $exception) {
            // Terminate method
            $this->addMessage($exception->getMessage(), 'error');
            return false;
        }

        // Find products that removed from skyroom panel
        $unsyncedProducts = array_filter($products, function ($product) use ($rooms) {
            $roomId = intval($product->getSkyroomId());
            return empty($roomId)
                || Utils::arrayFind($rooms, function ($room) use ($roomId) {
                    return $room->getId() === $roomId;
                }) === -1;
        });

        $count = count($unsyncedProducts);

        // Sync products with skyroom
        if ($count > 0) {
            $this->addMessage(
                sprintf(
                    _n('Found one product', 'Found %d products', $count, 'skyroom'),
                    number_format_i18n($count)
                ),
                'done',
                true
            );

            $this->addMessage(
                sprintf(
                    _n(
                        'Syncing one product with server...',
                        'Syncing %d products with server...',
                        $count,
                        'skyroom'
                    ),
                    'pending'
                )
            );

            foreach ($unsyncedProducts as $product) {
                $title = get_post_meta($product->getId(), PluginAdapterInterface::SKYROOM_ROOM_TITLE_META_KEY, true);
                $name = get_post_meta($product->getId(), PluginAdapterInterface::SKYROOM_ROOM_NAME_META_KEY, true);

                try {
                    $id = $this->roomRepository->createRoom(['title' => $title, 'name' => $name]);
                    update_post_meta($product->getId(), PluginAdapterInterface::SKYROOM_ID_META_KEY, $id);
                } catch (\Exception $e) {
                    $this->addMessage($e->getMessage() . sprintf(__('name: %s', 'skyroom'), $name), 'error');
                    return false;
                }
            }

            $this->addMessage(__('All products synced with skyroom', 'skyroom'), 'done', true);
        } else {
            $this->addMessage(__('All products already synced with skyroom', 'skyroom'), 'done', true);
        }

        return true;
    }

}
