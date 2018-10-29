<?php

namespace Skyroom\Tasks;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;
use Skyroom\Exception\RequestFailedException;
use Skyroom\Repository\UserRepository;

/**
 * Class ActualSyncDataTask
 *
 * @package Skyroom\Tasks
 */
class ActualSyncDataTask
{
    private $client;
    private $userRepository;
    private $pluginAdapter;
    private $wpdb;

    private $error = false;
    private $tasks;

    /**
     * SyncDataTask constructor. Initializes tasks.
     *
     * @param Client                 $client
     * @param UserRepository         $userRepository
     * @param PluginAdapterInterface $pluginAdapter
     * @param \wpdb                  $wpdb
     */
    public function __construct(Client $client, UserRepository $userRepository, PluginAdapterInterface $pluginAdapter, \wpdb $wpdb)
    {
        $this->client = $client;
        $this->userRepository = $userRepository;
        $this->pluginAdapter = $pluginAdapter;
        $this->wpdb = $wpdb;

        $this->tasks = [
            'initService',
            'syncUsers',
            'trackEnrolls',
            'syncEnrolls',
        ];
    }

    /**
     * Run individual task
     *
     * @param int $item Task index in array
     *
     * @return mixed
     */
    public function task($item)
    {
        if (call_user_func([$this, $this->tasks[$item]])) {
            $next = $item + 1;

            return $next < count($this->tasks) ? $next : false;
        } else {
            $this->error = true;
        }

        return false;
    }

    public function complete()
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

    public function initService()
    {
        $this->addMessage(__('Start sync service', 'skyroom'), 'done');

        return true;
    }

    public function syncUsers()
    {
        $this->addMessage(__('Syncing users with server...', 'skyroom'), 'pending');

        // Get users that don't have skyroom_id meta
        $unsyncedUsers = $this->userRepository->getUnsyncedWPUsers();

        if (empty($unsyncedUsers)) {
            $this->addMessage(__('All users are already synced with server', 'skyroom'), 'done', true);
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
            for ($i = 0, $count = count($unsyncedUsers); $i < $count; $i++) {
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
                $this->addMessage(__('Users synced with server successfully', 'skyroom'), 'done', true);

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

        $skyroomEnrollsTbl = $this->wpdb->prefix.'skyroom_enrolls';

        // Save untracked purchases in enrolls table
        if (count($untrackedPurchases) > 0) {
            foreach ($untrackedPurchases as $purchase) {
                $this->wpdb->insert($skyroomEnrollsTbl, $purchase);
            }

            $this->addMessage(__('Enrollments tracked successfully', 'skyroom'), 'done', true);
        } else {
            $this->addMessage(__('All enrollments are tracked already', 'skyroom'), 'done', true);
        }

        return true;
    }

    public function syncEnrolls()
    {
        $this->addMessage(__('Syncing enrollments with server...', 'skyroom'), 'pending');

        $skyroomEnrollsTbl = $this->wpdb->prefix.'skyroom_enrolls';

        // Get all unsynced enrolls
        $query = "SELECT skyroom_user_id, room_id FROM $skyroomEnrollsTbl WHERE synced = false";
        $unsyncedEnrolls = $this->wpdb->get_results($query);

        // Sync enrolls with server
        $roomUsersMap = [];
        if (count($unsyncedEnrolls) > 0) {
            foreach ($unsyncedEnrolls as $enroll) {
                $roomUsersMap[$enroll->room_id][] = ['user_id' => $enroll->skyroom_user_id];
            }
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
                $this->addMessage(__('Enrollments synced with server successfully', 'skyroom'), 'done', true);
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

        $this->addMessage(sprintf(__("Synced %d enrollments with server", 'skyroom'), count($unsyncedEnrolls)), 'done', true);

        return true;
    }
}