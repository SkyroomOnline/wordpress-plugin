<?php

namespace Skyroom\Repository;

use Skyroom\Api\Client;
use Skyroom\Entity\User;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;

/**
 * User Repository
 *
 * @package Skyroom\Repository
 */
class UserRepository
{
    const SKYROOM_ID_META_KEY = '_skyroom_id';

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
     * @throws ConnectionNotEstablishedException
     * @throws InvalidResponseStatusException
     * @throws \Skyroom\Exception\RequestFailedException
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
            'meta_name' => self::SKYROOM_ID_META_KEY,
            'meta_value' => $ids,
            'meta_compare' => 'IN',
        ]);

        $wpUsers = [];
        foreach ($wpUsersArray as $wpUser) {
            $wpUsers[$this->getSkyroomId($wpUser->ID)] = $wpUser;
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
     * @throws ConnectionNotEstablishedException
     * @throws InvalidResponseStatusException
     * @throws \Skyroom\Exception\RequestFailedException
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
        $this->updateSkyroomId($user->ID, $id);
    }

    /**
     * Add user to skyroom
     *
     * @throws ConnectionNotEstablishedException
     * @throws InvalidResponseStatusException
     * @throws \Skyroom\Exception\RequestFailedException
     *
     * @param \WP_User $user
     * @param integer  $roomId Room ID
     * @param integer  $postId Related wp post id
     */
    public function addUserToRoom($user, $roomId, $postId)
    {
        global $wpdb;

        $skyroomUserId = $this->getSkyroomId($user->ID);
        if (empty($skyroomUserId)) {
            throw new \InvalidArgumentException(__('User is not registered to skyroom', 'skyroom'));
        }

        $wpdb->insert(
            $wpdb->prefix.'skyroom_enrolls',
            [
                'skyroom_user_id' => $skyroomUserId,
                'room_id' => $roomId,
                'user_id' => $user->ID,
                'post_id' => $postId,
            ]
        );

        $this->client->request(
            'addRoomUsers',
            [
                'room_id' => $roomId,
                'users' => [
                    ['user_id' => $skyroomUserId],
                ],
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
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}skyroom_enrolls WHERE user_id=%d AND room_id=%d", $userId, $roomId);

        return !empty($wpdb->get_results($query));
    }

    /**
     * Get user skyroom id meta value
     *
     * @param $userId
     *
     * @return int
     */
    public function getSkyroomId($userId)
    {
        return get_user_meta($userId, self::SKYROOM_ID_META_KEY, true);
    }

    /**
     * Update skyroom id meta of user
     *
     * @param $userId
     * @param $skyroomUserId
     *
     * @return bool
     */
    public function updateSkyroomId($userId, $skyroomUserId)
    {
        return update_user_meta($userId, self::SKYROOM_ID_META_KEY, $skyroomUserId);
    }
}