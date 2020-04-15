<?php

namespace Skyroom\Repository;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Entity\Enrollment;
use Skyroom\Entity\ProductWrapperInterface;
use Skyroom\Entity\User;
use Skyroom\Exception\BatchOperationFailedException;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;
use Skyroom\Util\Utils;

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
     * @var PluginAdapterInterface
     */
    private $pluginAdapter;

    /**
     * User Repository constructor.
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
     * Get users
     *
     * @return User[]
     * @throws InvalidResponseStatusException
     * @throws \Skyroom\Exception\RequestFailedException
     *
     * @throws ConnectionNotEstablishedException
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
     * @param \WP_User $user User data
     * @throws InvalidResponseStatusException
     * @throws \Skyroom\Exception\RequestFailedException
     *
     * @throws ConnectionNotEstablishedException
     */
    public function addUser($user)
    {
        $params = [
            'username' => $this->generateUsername($user->ID),
            'password' => uniqid('', true),
            'nickname' => $user->display_name,
        ];

        $id = $this->client->request('createUser', $params);

        // Link skyroom user to wordpress
        $this->updateSkyroomId($user->ID, $id);
    }

    /**
     * Adds multiple users to skyroom
     *
     * @param $users \WP_User[]
     *
     * @throws ConnectionNotEstablishedException
     * @throws InvalidResponseStatusException
     * @throws \Skyroom\Exception\RequestFailedException
     * @throws BatchOperationFailedException
     */
    public function addUsers($users)
    {
        $params = [
            'users' => array_map(function (\WP_User $user) {
                return [
                    'username' => $this->generateUsername($user->ID),
                    'password' => uniqid('', true),
                    'nickname' => $user->display_name,
                ];
            }, $users),
        ];

        // Send request and get response
        $response = $this->client->request('createUsers', $params);

        $errors = false;
        for ($i = 0, $count = count($users); $i < $count; $i++) {
            if (is_int($response[$i])) {
                $this->updateSkyroomId($users[$i]->ID, $response[$i]);
            } else {
                $errors[] = sprintf(__('Error in saving \'%s\' to server: %s', 'skyroom'), $users[$i]->user_login, $response[$i]);
            }
        }

        if (!empty($errors)) {
            throw new BatchOperationFailedException($errors);
        }
    }

    /**
     * Check user reflected on skyroom (check by skyroom id)
     *
     * @param $user
     * @return bool User reflected or not
     */
    public function isSkyroomUserCreated($user)
    {
        return !is_null($this->getSkyroomId($user->id));
    }

    /**
     * Add user to skyroom
     *
     * @param \WP_User $user
     * @param integer $roomId Room ID
     * @param integer $postId Related wp post id
     * @throws ConnectionNotEstablishedException
     * @throws InvalidResponseStatusException
     * @throws \Skyroom\Exception\RequestFailedException
     *
     */
    public function addUserToRoom($user, $roomId, $postId)
    {
        global $wpdb;

        $skyroomUserId = $this->getSkyroomId($user->ID);
        if (empty($skyroomUserId)) {
            throw new \InvalidArgumentException(__('User is not registered to skyroom', 'skyroom'));
        }

        $wpdb->insert(
            $wpdb->prefix . 'skyroom_enrolls',
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
     * Get user enrolled rooms
     *
     * @param int $userId
     *
     * @return Enrollment[]
     */
    public function getUserEnrollments($userId)
    {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT room_id, enroll_time FROM {$wpdb->prefix}skyroom_enrolls WHERE user_id=%d ORDER BY enroll_time DESC",
            $userId
        );
        $enrolls = $wpdb->get_results($query);

        $rids = array_map(function ($enroll) {
            return $enroll->room_id;
        }, $enrolls);

        $prods = $this->pluginAdapter->getProducts($rids);
        $enrollments = array_map(function ($enroll) use ($prods) {
            $pIndex = Utils::arrayFind($prods, function (ProductWrapperInterface $prod) use ($enroll) {
                return $prod->getSkyroomId() === $enroll->room_id;
            });

            if ($pIndex >= 0) {
                return new Enrollment($prods[$pIndex], strtotime($enroll->enroll_time));
            } else {
                return null;
            }
        }, $enrolls);

        $enrollments = array_filter($enrollments);

        return $enrollments;
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

    /**
     * Generates random username for users to save on skyroom (for avoiding conflicts)
     *
     * @param $userId integer
     * @return string
     */
    public function generateUsername($userId)
    {
        return 'wp-user-' . $userId . '-' . rand(100000, 999999);
    }
}
