<?php

namespace Skyroom\Api;

use DI\NotFoundException;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\CreateRoomFailedDuplicateNameExeption;
use Skyroom\Exception\InternalServerErrorException;
use Skyroom\Exception\InvalidInputErrorException;
use Skyroom\Exception\InvalidResponseStatusException;
use Skyroom\Exception\NotFoundErrorException;
use Skyroom\Exception\RequestFailedException;

/**
 * Skyroom API client.
 *
 * @package Skyroom\Api
 */
class Client
{
    /**
     * @var URL $URL
     */
    private $URL;

    /**
     * Client constructor.
     *
     * @param URL $URL
     */
    public function __construct(URL $URL)
    {
        $this->URL = $URL;
    }

    /**
     * Set url object
     *
     * @param URL $URL
     */
    public function setURL(URL $URL)
    {
        $this->URL = $URL;
    }

    /**
     * Sends a request to webservice and returns response
     *
     * @param string $action Requested action
     * @param array $params Request parameters
     *
     * @return mixed Webservice result
     * @throws InvalidResponseStatusException
     * @throws RequestFailedException
     *
     * @throws ConnectionNotEstablishedException
     */
    public function request($action, $params = null)
    {
        $data = $this->URL->toString();
        $url = $data['url'];

        switch ($action) {
            case 'ping':
                $url = $url . 'info';
                $response = $this->get_method($data['api'], $url);
                break;
            case 'getRooms':
                $url = $url . 'rooms';
                $response = $this->get_method($data['api'], $url);
                break;
            case 'createRoom':
                $url = $url . 'rooms';
                $response = $this->post_method($data['api'], $params, $url);
                break;
            case 'updateRoom':
                $url = $url . 'rooms/' . 250;
                $response = $this->patch_method($data['api'], $params, $url);
                break;
            case 'getRoom':
                $url = $url . 'rooms/' . $params['room_id'];
                $response = $this->get_method($data['api'], $url);
                break;
            case 'getLoginUrl':
                $url = $url . 'rooms/' . $params['channelId'] . '/attendee';
                $paramsLink = [
                    'id' => $params['id'],
                    'nickname' => $params['nickname'],
                    'role' => $params['role']
                ];
                $response = $this->post_method($data['api'], $paramsLink, $url);
                break;
        }
        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body);


        if ($status == 500) {
            throw new InternalServerErrorException();
        }
        if ($status == 400) {
            throw new InvalidInputErrorException();
        }
        if ($status == 404) {
            throw new NotFoundErrorException();
        }

        if (empty($status)) {
            throw new ConnectionNotEstablishedException();
        }
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseStatusException();
        }

        if ($action == 'createRoom') {
            return $result->id;
        }

        if ($action == 'getRoom' || $action == 'ping' || $action == 'getLoginUrl') {
            return $result;
        }
        return $result->items;
    }

    private function post_method($api, $params, $url)
    {
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $api,
            ),
            'body' => json_encode($params),
            'timeout' => 60,
        );
        $response = wp_remote_post($url, $args);
        return $response;
    }

    private function get_method($api, $url)
    {
        $args = [
            'headers' => array(
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $api,
            ),
            'method' => 'GET',
            'timeout' => 60,
        ];
        $response = wp_remote_post($url, $args);
        return $response;
    }

    private function patch_method($api, $params, $url)
    {
        $args = array(
            'method' => 'PATCH',
            'headers' => array(
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $api,
            ),
            'body' => json_encode($params),
            'timeout' => 60,
        );
        $response = wp_remote_post($url, $args);
        return $response;
    }
}