<?php

namespace Skyroom\Repository;

use Skyroom\Api\Client;
use Skyroom\Exception\ConnectionTimeoutException;
use Skyroom\Exception\InvalidResponseException;

/**
 * Room Repository
 *
 * @package Skyroom\Repository
 */
class RoomRepository
{
    /**
     * @var Client API client
     */
    private $client;

    /**
     * Room Repository constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get rooms
     *
     * @throws ConnectionTimeoutException
     * @throws InvalidResponseException
     *
     * @return array
     */
    public function getRooms()
    {
        $roomsArray = $this->client->request('getRooms');

        return $roomsArray;
    }
}