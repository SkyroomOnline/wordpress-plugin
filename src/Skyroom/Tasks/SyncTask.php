<?php

namespace Skyroom\Tasks;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Exception\BatchOperationFailedException;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;
use Skyroom\Exception\RequestFailedException;
use Skyroom\Repository\UserRepository;
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
    private $pluginAdapter;
    private $wpdb;

    private $error = false;
    private $tasks;

    /**
     * SyncDataTaskRunner constructor. Initializes tasks.
     *
     * @param Client $client
     * @param UserRepository $userRepository
     * @param PluginAdapterInterface $pluginAdapter
     * @param wpdb $wpdb
     */
    public function __construct(Client $client, UserRepository $userRepository, PluginAdapterInterface $pluginAdapter, wpdb $wpdb)
    {
        parent::__construct();

        $this->client = $client;
        $this->userRepository = $userRepository;
        $this->pluginAdapter = $pluginAdapter;
        $this->wpdb = $wpdb;
        $this->tasks = [
            'syncEnrolls'
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

    public function syncEnrolls()
    {
        // Set start message done
        $this->addMessage(__('Start sync service', 'skyroom'), 'done', true);

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
            $userSkyroomIdsMap = array_reduce($userIds, function ($acc, $userId) {
                $acc[$userId] = $this->userRepository->getSkyroomId($userId);
                return $acc;
            }, []);

            $notCreatedUsers = array_filter($userSkyroomIdsMap, function ($val) {
                return empty($val);
            });

            if (count($notCreatedUsers) > 0) {
                // Try to add users to skyroom
                $error = true;
                try {
                    $users = get_users(['include' => array_keys($notCreatedUsers)]);
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
                $roomUsersMap[$enroll['room_id']][] = ['user_id' => $this->userRepository->getSkyroomId($enroll['user_id'])];
            }

            try {
                $resultMap = $this->client->request(
                    'syncRoomUsers',
                    [
                        'room_users' => $roomUsersMap,
                    ]
                );

                $error = false;
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

                    $userIds = [];
                    foreach ($usersResult as $i => $userResult) {
                        if (is_numeric($userResult)) {
                            $userIds[] = $roomUsersMap[$roomId][$i]['user_id'];
                        } else {
                            $this->addMessage(
                                sprintf(
                                    __('Error in syncing room(%d) user(%d): %s', 'skyroom'),
                                    $roomId,
                                    $roomUsersMap[$roomId][$i]['user_id'],
                                    $usersResult
                                ),
                                'error',
                                !$error
                            );

                            $error = true;
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
            }
        } else {
            $this->addMessage(__('All enrollments already synced with skyroom', 'skyroom'), 'done', true);
        }

        return true;
    }
}
