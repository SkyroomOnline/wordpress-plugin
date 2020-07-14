<?php

namespace Skyroom\Repository;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Entity\User;
use Skyroom\Exception\BatchOperationFailedException;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;
use Skyroom\Exception\RequestFailedException;
use function DI\object;

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
     * @param EventRepository $eventRepository
     * @param PluginAdapterInterface $pluginAdapter
     */
    public function __construct(Client $client, EventRepository $eventRepository, PluginAdapterInterface $pluginAdapter, EventRepository $event)
    {
        $this->client = $client;
        $this->pluginAdapter = $pluginAdapter;
        $this->event = $event;
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
        $events = $this->event->getAll();
        $usersArray = [];
        foreach ($events as $ev) {
            $usersArray[] = $ev->getErrorInfo();
        }
        $usersArrayFinal = [];
        foreach ($usersArray as $user) {
            if (!empty($user['item_id'])) {
                $userData = get_userdata($user['user_id']);
                $product = wc_get_product($user['item_id']);
                $usersArrayFinal[] = (object)array(
                    'id' => $user['user_id'],
                    'product' => $product->name,
                    'nickname' => $userData->display_name,
                    'status' => 1,
                );
//                print_r($product->name." | ");
            }
        }

//        die(1);

        $ids = array_map(function ($user) {
            return $user->id;
        }, $usersArrayFinal);

        $wpUsersArray = get_users([
            'meta_name' => self::SKYROOM_ID_META_KEY,
            'meta_value' => $ids,
            'meta_compare' => 'IN',
        ]);

        $wpUsers = [];
        $res = [];
        foreach ($wpUsersArray as $wpUser) {
            $wpUsers[$this->getSkyroomId($wpUser->ID)] = $wpUser;
            $res [] = $wpUser->ID;
//                $res [] = $this->getSkyroomId($wpUser->ID);
        }


        $users = [];
        foreach ($usersArrayFinal as $user) {
            $users[] = new User($user, isset($wpUsers[$user->id]) ? $wpUsers[$user->id] : null);
        }
//        print_r($users);
//        die(1);
        return $users;
    }

    /**
     * Add registered user to skyroom
     *
     * @param \WP_User $user User data
     *
     * @throws InvalidResponseStatusException
     * @throws \Skyroom\Exception\RequestFailedException
     * @throws ConnectionNotEstablishedException
     */
    public function addUser($user)
    {
        $this->updateSkyroomId($user->ID, $user->ID);
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
     * @param int $userId
     * @return bool User reflected or not
     */
    public function hasSkyroomUser($userId)
    {
        return !empty($this->getSkyroomId($userId));
    }

    /**
     * Ensure that sykroom user added for given wp_user, if it's not added, add it
     *
     * @param \WP_User $user
     *
     * @throws ConnectionNotEstablishedException
     * @throws InvalidResponseStatusException
     * @throws RequestFailedException
     */
    public function ensureSkyroomUserAdded($user)
    {
        if (!$this->hasSkyroomUser($user->ID)) {
            $this->addUser($user);
        }
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
