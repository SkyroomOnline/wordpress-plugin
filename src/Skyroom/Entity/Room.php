<?php

namespace Skyroom\Entity;

/**
 * Room entity
 *
 * @package Skyroom\Entity
 */
class Room
{
    /**
     * @var int Room ID
     */
    private $id;

    /**
     * @var string Room name
     */
    private $name;

    /**
     * @var string Room title
     */
    private $title;

    /**
     * @var int Room status
     */
    private $status;

    /**
     * @var ProductWrapperInterface Room related product
     */
    private $product;

    /**
     * Room constructor.
     *
     * @param object                  $room
     * @param ProductWrapperInterface $product
     */
    public function __construct($room, $product)
    {
        $this->id = $room->id;
        $this->name = $room->name;
        $this->title = $room->title;
        $this->status = $room->status;
        $this->product = $product;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getStatusAsString()
    {
        switch ($this->status) {
            case 1:
                return __('Active', 'skyroom');

            case 0:
                return __('Inactive', 'skyroom');

            default:
                return '';
        }
    }

    /**
     * @return ProductWrapperInterface
     */
    public function getProduct()
    {
        return $this->product;
    }
}
