<?php

namespace Skyroom\Entity;

/**
 * User entity
 *
 * @package Skyroom\Entity
 */
class User
{
    /**
     * @var int User ID (on skyroom)
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $nickname;

    /**
     * @var int
     */
    private $status;

    /**
     * @var \WP_User User object in wp
     */
    private $wpUser;

    /**
     * User constructor.
     *
     * @param object   $user
     * @param \WP_User $wpUser
     */
    public function __construct($user, $wpUser)
    {
        $this->id = $user->id;
        $this->username = $user->username;
        $this->nickname = $user->nickname;
        $this->status = $user->status;
        $this->wpUser = $wpUser;
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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
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
     * @return \WP_User
     */
    public function getWpUser()
    {
        return $this->wpUser;
    }
}
