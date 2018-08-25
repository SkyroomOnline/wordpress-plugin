<?php

namespace Skyroom\Api;

use Skyroom\Exception\ConnectionTimeoutException;
use Skyroom\Exception\InvalidResponseException;

/**
 * Skyroom API client.
 *
 * @package Skyroom\Api
 */
class Client
{
    /**
     * @var URLManager $URLManager
     */
    private $URLManager;

    /**
     * Client constructor.
     *
     * @param URLManager $URLManager
     */
    public function __construct(URLManager $URLManager)
    {
        $this->URLManager = $URLManager;
    }

    /**
     * Sends a request to webservice and returns response
     *
     * @param string $action Requested action
     * @param array  $params Request parameters
     *
     * @throws ConnectionTimeoutException
     * @throws InvalidResponseException
     *
     * @return mixed Webservice result
     */
    public function request($action, $params = null)
    {
        $url = $this->URLManager->getURL();
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
        ];

        $response = wp_remote_post($url, $args);
        $status = wp_remote_retrieve_response_code($response);
        if (empty($status)) {
            throw new ConnectionTimeoutException();
        }

        if ($status !== 200) {
            throw new InvalidResponseException(InvalidResponseException::INVALID_RESPONSE_STATUS);
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body);

        if ($result === null && json_last_error() !== JSON_ERROR_NONE
            || !property_exists($result, 'result')
            || !property_exists($result, 'ok')
        ) {
            throw new InvalidResponseException(InvalidResponseException::INVALID_RESPONSE_CONTENT);
        }

        if ($result->ok) {
            return $result->result;
        }

        throw new InvalidResponseException(InvalidResponseException::INVALID_RESULT);
    }
}