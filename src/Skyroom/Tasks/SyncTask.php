<?php

namespace Skyroom\Tasks;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
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
            'syncUsers',
            'trackEnrolls',
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
        $this->addMessage(__('Start sync service', 'skyroom'), 'done');

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

        $index = $replace ? count($status['messages']) - 1 : count($status['messages']);
        $status['messages'][$index] = [
            'message' => $message,
            'type' => $type,
        ];

        set_transient('skyroom_sync_data_status', $status);
    }

    public function syncUsers()
    {
        $this->addMessage(__('Syncing users with server...', 'skyroom'), 'pending');

        // Get users that don't have skyroom_id meta
        $unsyncedUsers = $this->userRepository->getUnsyncedWPUsers();

        if (empty($unsyncedUsers)) {
            $this->addMessage(__('All users are already synced with server', 'skyroom'), 'done', true);

            return true;
        }

        try {
            $response = $this->client->request(
                'createUsers',
                [
                    'users' => array_map(function ($user) {
                        return [
                            'username' => $user->user_login,
                            'email' => $user->user_email,
                            'nickname' => $user->user_nicename,
                            'password' => uniqid('', true),
                        ];
                    }, $unsyncedUsers),
                ]
            );

            $error = false;
            $count = $count = count($unsyncedUsers);
            for ($i = 0; $i < $count; $i++) {
                if (is_int($response[$i])) {
                    update_user_meta($unsyncedUsers[$i]->ID, UserRepository::SKYROOM_ID_META_KEY, $response[$i]);
                } else {
                    $this->addMessage(
                        sprintf(__('Error in saving \'%s\' to server: %s', 'skyroom'), $unsyncedUsers[$i]->user_login, $response[$i]),
                        'error',
                        !$error
                    );
                    $error = true;
                }
            }

            if (!$error) {
                $this->addMessage(
                    sprintf(
                        _n(
                            '%d user synced with server successfully',
                            '%d users synced with server successfully',
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

            return false;

        } catch (ConnectionNotEstablishedException $e) {
            $this->addMessage($e->getMessage(), 'error');

            return false;
        } catch (InvalidResponseStatusException $e) {
            $this->addMessage($e->getMessage(), 'error');

            return false;
        } catch (RequestFailedException $e) {
            $this->addMessage($e->getMessage(), 'error');

            return false;
        }
    }

    public function trackEnrolls()
    {
        $this->addMessage(__('Finding untracked enrollments...', 'skyroom'), 'pending');

        // Get purchases that are not saved on skyroom_enrolls table
        $untrackedPurchases = $this->pluginAdapter->getUntrackedPurchases();
        $count = count($untrackedPurchases);

        $skyroomEnrollsTbl = $this->wpdb->prefix . 'skyroom_enrolls';

        // Save untracked purchases in enrolls table
        if ($count > 0) {
            foreach ($untrackedPurchases as $purchase) {
                $this->wpdb->insert($skyroomEnrollsTbl, $purchase);
            }

            $this->addMessage(
                sprintf(
                    _n(
                        '%d enrollment tracked successfully',
                        '%d enrollments tracked successfully',
                        $count,
                        'skyroom'
                    ),
                    number_format_i18n($count)
                ),
                'done',
                true
            );
        } else {
            $this->addMessage(__('All enrollments are tracked already', 'skyroom'), 'done', true);
        }

        return true;
    }

    public function syncEnrolls()
    {
        $this->addMessage(__('Syncing enrollments with server...', 'skyroom'), 'pending');

        $skyroomEnrollsTbl = $this->wpdb->prefix . 'skyroom_enrolls';

        // Get all unsynced enrolls
        $query = "SELECT skyroom_user_id, room_id FROM $skyroomEnrollsTbl WHERE synced = false";
        $unsyncedEnrolls = $this->wpdb->get_results($query);
        $count = count($unsyncedEnrolls);

        // Sync enrolls with server
        $roomUsersMap = [];
        if ($count > 0) {
            foreach ($unsyncedEnrolls as $enroll) {
                $roomUsersMap[$enroll->room_id][] = ['user_id' => $enroll->skyroom_user_id];
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

                    $userIds = implode(',', $userIds);
                    $query = "UPDATE $skyroomEnrollsTbl SET synced = true WHERE room_id = $roomId AND skyroom_user_id IN ($userIds)";
                    $this->wpdb->query($query);
                }

                if ($error) {
                    return false;
                } else {
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
            }
        } else {
            $this->addMessage(sprintf(__("All enrollments already synced with server", 'skyroom'), count($unsyncedEnrolls)), 'done', true);

            return true;
        }
    }
}
