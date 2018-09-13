<?php

namespace Skyroom\Entity;

/**
 * Event entity
 *
 * @package Skyroom\Entity
 */
class Event
{
    /**
     * Event constants for type
     */
    const SUCCESSFUL = 1;
    const FAILED = 2;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $type;

    /**
     * @var array Info about the occurred error
     */
    private $errorInfo;

    /**
     * @var int Creation timestamp
     */
    private $createdAt;

    /**
     * Event constructor.
     *
     * @param string $title
     * @param int    $type
     * @param array  $errorInfo
     */
    public function __construct($title, $type, $errorInfo)
    {
        $this->title = $title;
        $this->type = $type;
        $this->errorInfo = $errorInfo;
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getErrorInfo()
    {
        return $this->errorInfo;
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}