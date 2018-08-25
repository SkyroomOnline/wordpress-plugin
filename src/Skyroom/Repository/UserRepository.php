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
}