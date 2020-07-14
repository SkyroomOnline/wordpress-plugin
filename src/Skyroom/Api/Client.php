<?php

namespace Skyroom\Api;

use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;
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
        $body = [];
        $body['action'] = $action;
        if (!empty($params)) {
            $body['params'] = $params;
        }
        $url = $data['url'];
        if ($action == 'ping') {
            $url = $url . 'info';
            $method = 'GET';
        }
        if ($action == 'getRooms') {
            $url = $url . 'rooms';
            $method = 'GET';
        }
        if ($action == 'createRoom') {
            $url = $url . 'rooms';
            $method = 'POST';
        }
        if ($action == 'updateRoom'){
            $url = $url . 'rooms/'.$params['room_id'];
            $method = 'PATCH';
        }
        if ($action == 'getRoom'){
            $url = $url . 'rooms/'.$params['room_id'];
            $method = 'GET';
        }
        if ($action == 'getLoginUrl'){
            $url = $url . 'attendees/token';
            $method = 'POST';
        }


        if ($method == 'GET') {
            $args = [
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $data['api'],
                ),
                'method' => $method,
                'timeout' => 60,
            ];
        } elseif ($method == 'POST' || $method == 'PATCH') {
            $args = array(
                    'method' => $method,
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $data['api'],
                    ),
                    'body' => json_encode($params),
                    'timeout' => 60,
            );
        }

        $response = wp_remote_post($url, $args);
        $status = wp_remote_retrieve_response_code($response);


//        print_r($response);
//        die(1);

        if (empty($status)) {
            throw new ConnectionNotEstablishedException();
        }

        if ($status < 200 || $status > 299) {
            throw new InvalidResponseStatusException();
        }


        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body);

        if ($action == 'createRoom') {
            return $result->id;
        }

        if ($action == 'getRoom' || $action == 'ping' || $action == 'getLoginUrl') {
            return $result;
        }

//        print_r($body);
//        die(1);

//        if ($result === null && json_last_error() !== JSON_ERROR_NONE
//            || !property_exists($result, 'ok')
//        ) {
//            throw new InvalidResponseStatusException();
//        }
//
//        if ($result->ok === false) {
//            throw new RequestFailedException($result->error_code, $result->error_message);
//        }
//        print_r($result->items);
        return $result->items;
    }
}