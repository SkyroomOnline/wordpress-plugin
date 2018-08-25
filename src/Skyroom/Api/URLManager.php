<?php

namespace Skyroom\Api;

/**
 * Creates webservice url
 *
 * @package Skyroom\Api
 */
class URLManager
{
    /**
     * @var string $url Stored webservice url
     */
    private $url;

    /**
     * URLManager constructor.
     *
     * @param string $siteUrl
     * @param string $apiKey
     */
    public function __construct($siteUrl, $apiKey)
    {
        $this->url = rtrim($siteUrl, '/').'/skyroom/api/'.$apiKey;
    }

    /**
     * Get webservice url
     *
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }
}