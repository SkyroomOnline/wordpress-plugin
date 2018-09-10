<?php

namespace Skyroom\Repository;

use Skyroom\Api\Client;
use Skyroom\Entity\User;
use Skyroom\Exception\ConnectionTimeoutException;
use Skyroom\Exception\InvalidResponseException;
use Skyroom\WooCommerce\SkyroomProduct;

/**
 * User Repository
 *
 * @package Skyroom\Repository
 */
class UserRepository
{
    /**
     * @var Client API client
     */
    private $client;

    /**
     * User Repository constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get users
     *
     * @throws ConnectionTimeoutException
     * @throws InvalidResponseException
     *
     * @return User[]
     */
    public function getUsers()
    {
        $usersArray = $this->client->request('getUsers');
        $ids = array_map(function ($user) {
            return $user->id;
        }, $usersArray);

        $wpUsersArray = get_users([
            'meta_name' => '_skyroom_id',
            'meta_value' => $ids,
            'meta_compare' => 'IN',
        ]);

        $wpUsers = [];
        foreach ($wpUsersArray as $wpUser) {
            $wpUsers[get_user_meta($wpUser->ID, '_skyroom_id', true)] = $wpUser;
        }

        $users = [];
        foreach ($usersArray as $user) {
            $users[] = new User($user, isset($wpUsers[$user->id]) ? $wpUsers[$user->id] : null);
        }

        return $users;
    }

    /**
     * Add registered user to skyroom
     *
     * @throws ConnectionTimeoutException
     * @throws InvalidResponseException
     *
     * @param \WP_User $user User data
     */
    public function addUser($user)
    {
        $params = [
            'username' => $user->user_login,
            'password' => uniqid('', true),
            'email' => $user->user_email,
            'nickname' => $user->display_name,
        ];

        $id = $this->client->request('createUser', $params);

        // Link skyroom user to wordpress
        update_user_meta($user->ID, 'skyroom_id', $id);
    }

    /**
     * Add user to skyroom
     *
     * @throws ConnectionTimeoutException
     * @throws InvalidResponseException
     *
     * @param \WP_User $user
     * @param integer  $roomId Room ID
     * @param integer  $postId Related wp post id
     */
    public function addUserToRoom($user, $roomId, $postId)
    {
        global $wpdb;

        $skyroomUserId = get_user_meta($user->id, '_skyroom_id');
        if (empty($skyroomUserId)) {
            throw new \InvalidArgumentException(__('User is not registered to skyroom', 'skyroom'));
        }

        $this->client->request(
            'addRoomUsers',
            [
                'room_id' => $postId,
                'users' => [
                    ['user_id' => $user->id],
                ],
            ]
        );

        $wpdb->insert(
            $wpdb->prefix.'skyroom_enrolls',
            [
                'user_id' => $user->id,
                'room_id' => $roomId,
                'post_id' => $postId,
            ]
        );
    }

    /**
     * Check whether user is in room or not (Purchased, enrolled, ...)
     *
     * @param integer $userId
     * @param integer $roomId
     *
     * @return bool
     */
    public function isUserInRoom($userId, $roomId)
    {
        global $wpdb;

        $wpdb->prepare('SELECT FROM {$wpdb->prefix}skyroom_user WHERE user_id=%d AND room_id=%d', $userId, $roomId);

        return !empty($wpdb->get_results());
    }
}