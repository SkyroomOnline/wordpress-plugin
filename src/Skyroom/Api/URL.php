<?php

namespace Skyroom\Api;

/**
 * Creates webservice url
 *
 * @package Skyroom\Api
 */
class URL
{
    /**
     * @var string $siteUrl
     */
    private $siteUrl;

    /**
     * @var string $apiKey
     */
    private $apiKey;

    /**
     * URLManager constructor.
     *
     * @param string $siteUrl
     * @param string $apiKey
     */
    public function __construct($siteUrl, $apiKey)
    {
        $this->siteUrl = $siteUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * Get webservice url
     *
     * @return string
     */
    public function toString()
    {
        $data['url'] = 'https://api.skyroom.online/v2/';
        $data['api'] =  $this->apiKey;
        return $data;
    }
}