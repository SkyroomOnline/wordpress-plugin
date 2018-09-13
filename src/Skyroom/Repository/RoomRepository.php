<?php

namespace Skyroom\Repository;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Entity\Room;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;

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
     * @param Client                 $client
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
     * @throws ConnectionNotEstablishedException
     * @throws InvalidResponseStatusException
     * @throws \Skyroom\Exception\RequestFailedException
     *
     * @return Room[]
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
}