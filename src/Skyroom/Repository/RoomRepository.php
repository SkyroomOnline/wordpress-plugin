<?php

namespace Skyroom\Repository;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Entity\Room;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;
use Skyroom\Exception\RequestFailedException;

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
     * @var PluginAdapterInterface Plugin adapter
     */
    private $pluginAdapter;

    /**
     * Room Repository constructor.
     *
     * @param Client $client
     * @param PluginAdapterInterface $pluginAdapter
     */
    public function __construct(Client $client, PluginAdapterInterface $pluginAdapter)
    {
        $this->client = $client;
        $this->pluginAdapter = $pluginAdapter;
    }

    /**
     * Get rooms
     *
     * @return Room[]
     * @throws InvalidResponseStatusException
     * @throws RequestFailedException
     *
     * @throws ConnectionNotEstablishedException
     */
    public function getRooms()
    {
        $roomsArray = $this->client->request('getRooms');
        $ids = array_map(function ($room) {
            return $room->id;
        }, $roomsArray);

        $prods = $this->pluginAdapter->getProducts($ids);
        $products = [];
        foreach ($prods as $product) {
            $products[$product->getSkyroomId()] = $product;
        }

        $rooms = [];
        foreach ($roomsArray as $room) {
            $product = isset($products[$room->id]) ? $products[$room->id] : null;
            $rooms[] = new Room($room, $product);
        }

        return $rooms;
    }

    /**
     * Get post type string
     *
     * @param bool $plural
     *
     * @return string
     */
    public function getPostString($plural = false)
    {
        return $this->pluginAdapter->getPostString($plural);
    }

    /**
     * Create room on skyroom API
     *
     * @param $data array
     *
     * @return integer ID of created room
     *
     * @throws ConnectionNotEstablishedException
     * @throws InvalidResponseStatusException
     * @throws RequestFailedException
     */
    function createRoom($data)
    {
        $params = [
            'name' => $data['name'],
            'title' => $data['title'],
            'opLoginFirst' => isset($data['opLoginFirst']) ? $data['opLoginFirst'] : false,
        ];

        return $this->client->request('createRoom', $params);
    }

    function updateRoom($id, $data)
    {
        $params = ['room_id' => $id];
        if (isset($data['name'])) {
            $params['name'] = $data['name'];
        }

        if (isset($data['title'])) {
            $params['title'] = $data['title'];
        }

        if (isset($data['opLoginFirst'])) {
            $params['op_login_first'] = $data['opLoginFirst'];
        }

        return $this->client->request('updateRoom', $params);
    }
}