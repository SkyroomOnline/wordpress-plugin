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
     * @param array  $params Request parameters
     *
     * @throws ConnectionNotEstablishedException
     * @throws InvalidResponseStatusException
     * @throws RequestFailedException
     *
     * @return mixed Webservice result
     */
    public function request($action, $params = null)
    {
        $url = $this->URL->toString();
        $body = [];
        $body['action'] = $action;
        if (!empty($params)) {
            $body['params'] = $params;
        }

        $args = [
            'headers' => array(
                'Content-Type' => 'application/json; charset=UTF-8',
            ),
            'body' => json_encode($body),
            'timeout' => 60,
        ];

        $response = wp_remote_post($url, $args);
        $status = wp_remote_retrieve_response_code($response);
        if (empty($status)) {
            throw new ConnectionNotEstablishedException();
        }

        if ($status !== 200) {
            throw new InvalidResponseStatusException();
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body);

        if ($result === null && json_last_error() !== JSON_ERROR_NONE
            || !property_exists($result, 'ok')
        ) {
            throw new InvalidResponseStatusException();
        }

        if ($result->ok === false) {
            throw new RequestFailedException($result->error_code, $result->error_message);
        }

        return $result->result;
    }
}