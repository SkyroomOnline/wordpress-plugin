<?php

namespace Skyroom\Repository;

use Skyroom\Api\Client;
use Skyroom\Exception\ConnectionTimeoutException;
use Skyroom\Exception\InvalidResponseException;

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
     * @return array
     */
    public function getUsers()
    {
        $roomsArray = $this->client->request('getUsers');

        return $roomsArray;
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
}