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
use Skyroom\Repository\UserRepository;
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
    private $userRepository;
    private $roomRepository;
    private $pluginAdapter;
    private $wpdb;

    private $error = false;
    private $tasks;

    /**
     * SyncDataTaskRunner constructor. Initializes tasks.
     *
     * @param Client $client
     * @param UserRepository $userRepository
     * @param RoomRepository $roomRepository
     * @param PluginAdapterInterface $pluginAdapter
     * @param wpdb $wpdb
     */
    public function __construct(
        Client $client,
        UserRepository $userRepository,
        RoomRepository $roomRepository,
        PluginAdapterInterface $pluginAdapter,
        wpdb $wpdb
    )
    {
        parent::__construct();

        $this->client = $client;
        $this->userRepository = $userRepository;
        $this->roomRepository = $roomRepository;
        $this->pluginAdapter = $pluginAdapter;
        $this->wpdb = $wpdb;
        $this->tasks = [
            'syncRooms',
//            'syncEnrolls'
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

    public function syncEnrolls()
    {
        // Add new message
        $this->addMessage(__('Finding unsynced enrollments...', 'skyroom'), 'pending');

        // Get purchases that are not saved on skyroom
        $unsyncedEnrolls = $this->pluginAdapter->getUnsyncedEnrolls();
        $count = count($unsyncedEnrolls);

        // Sync enrollments with skyroom
        if ($count > 0) {
            $this->addMessage(
                sprintf(
                    _n('Found one enrollment', 'Found %d enrollments', $count, 'skyroom'),
                    number_format_i18n($count)
                ),
                'done',
                true
            );

            $this->addMessage(
                sprintf(
                    _n(
                        'Syncing one enrollment with server...',
                        'Syncing %d enrollments with server...',
                        $count,
                        'skyroom'
                    ),
                    'pending'
                )
            );

            // Find users that not created on skyroom yet
            $userIds = array_unique(array_map(function ($enroll) {
                return $enroll['user_id'];
            }, $unsyncedEnrolls));

            $notCreatedUsers = array_filter($userIds, function ($val) {
                return empty($this->userRepository->getSkyroomId($val));
            });

            if (count($notCreatedUsers) > 0) {
                // Try to add users to skyroom
                $error = true;
                try {
                    $users = get_users(['include' => $notCreatedUsers]);
                    $this->userRepository->addUsers($users);
                    $error = false;
                } catch (ConnectionNotEstablishedException $e) {
                    $this->addMessage($e->getMessage(), 'error');
                } catch (InvalidResponseStatusException $e) {
                    $this->addMessage($e->getMessage(), 'error');
                } catch (RequestFailedException $e) {
                    $this->addMessage($e->getMessage(), 'error');
                } catch (BatchOperationFailedException $e) {
                    for ($i = 0, $count = count($e->errors); $i < $count; $i++) {
                        $this->addMessage($e->errors[$i], 'error', $i === 0);
                    }
                }

                if ($error) {
                    return false;
                }
            }

            $roomUsersMap = [];
            foreach ($unsyncedEnrolls as $enroll) {
                $roomUsersMap[$enroll['room_id']][] =
                    [
                        'user_id' => $this->userRepository->getSkyroomId($enroll['user_id']),
                        'wp_user_id' => $enroll['user_id']
                    ];
            }

            try {
                $resultMap = $this->client->request('syncRoomUsers', ['room_users' => $roomUsersMap]);

                $error = false;
                $notFoundUsers = new Set();
                $retryArray = [];
                foreach ($resultMap as $roomId => $usersResult) {
                    if (!is_array($usersResult)) {
                        $this->addMessage(
                            sprintf(__('Error in syncing room(%d) users: %s', 'skyroom'), $roomId, $usersResult),
                            'error',
                            !$error
                        );

                        $error = true;
                        continue;
                    }

                    foreach ($usersResult as $i => $userResult) {
                        if (!is_numeric($userResult)) {
                            if (is_object($userResult) && $userResult->error->code === 404) {
                                $notFoundUsers->add($roomUsersMap[$roomId][$i]['wp_user_id']);
                                $retryArray[] = [
                                    'room_id' => $roomId,
                                    'user_id' => $roomUsersMap[$roomId][$i]['wp_user_id'],
                                ];
                            } else {
                                $this->addMessage(
                                    sprintf(
                                        __('Error in syncing room(%d) user(%d): %s', 'skyroom'),
                                        $roomId,
                                        $roomUsersMap[$roomId][$i]['user_id'],
                                        $userResult->message
                                    ),
                                    'error',
                                    !$error
                                );

                                $error = true;
                            }
                        }
                    }
                }

                if ($error) {
                    return false;
                }

                if ($notFoundUsers->count() > 0) {
                    // There's users removed from skyroom! Let's recreate then
                    $users = get_users(['include' => $notFoundUsers->toArray()]);
                    $this->userRepository->addUsers($users);

                    // Users created, retry adding enrollments
                    $roomUsersMap = [];
                    foreach ($retryArray as $enroll) {
                        $roomUsersMap[$enroll['room_id']][] =
                            ['user_id' => $this->userRepository->getSkyroomId($enroll['user_id'])];
                    }
                    $resultMap = $this->client->request('syncRoomUsers', ['room_users' => $roomUsersMap]);

                    foreach ($resultMap as $roomId => $usersResult) {
                        foreach ($usersResult as $i => $userResult) {
                            if (!is_numeric($userResult)) {
                                $this->addMessage(
                                    sprintf(
                                        __('Error in syncing room(%d) user(%d): %s', 'skyroom'),
                                        $roomId,
                                        $roomUsersMap[$roomId][$i]['user_id'],
                                        $userResult->error->message
                                    ),
                                    'error',
                                    !$error
                                );

                                $error = true;
                            }
                        }
                    }
                }

                if ($error) {
                    return false;
                } else {
                    $this->pluginAdapter->setEnrollmentsSynced(
                        array_map(function ($enroll) {
                            return $enroll['item_id'];
                        }, $unsyncedEnrolls));

                    $this->addMessage(
                        sprintf(
                            _n(
                                '%d enrollment synced with server successfully',
                                '%d enrollments synced with server successfully',
                                $count,
                                'skyroom'
                            ),
                            number_format_i18n($count)
                        ),
                        'done',
                        true
                    );

                    return true;
                }

            } catch (ConnectionNotEstablishedException $e) {
                $this->addMessage($e->getMessage(), 'error');

                return false;

            } catch (InvalidResponseStatusException $e) {
                $this->addMessage($e->getMessage(), 'error');

                return false;

            } catch (RequestFailedException $e) {
                $this->addMessage($e->getMessage(), 'error');

                return false;
            } catch (BatchOperationFailedException $e) {
                for ($i = 0, $count = count($e->errors); $i < $count; $i++) {
                    $this->addMessage($e->errors[$i], 'error', $i === 0);
                }

                return false;
            }
        } else {
            $this->addMessage(__('All enrollments already synced with skyroom', 'skyroom'), 'done', true);
        }

        return true;
    }
}
