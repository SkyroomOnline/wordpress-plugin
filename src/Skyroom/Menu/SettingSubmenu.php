<?php

namespace Skyroom\Menu;

use Skyroom\Api\Client;
use Skyroom\Repository\UserRepository;

/**
 * User submenu
 *
 * @package Skyroom\Menu
 */
class SettingSubmenu extends AbstractSubmenu
{
    /**
     * @var Client $client
     */
    private $client;

    /**
     * Setting submenu constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;

        // Set setting menu attributes
        parent::__construct(
            'skyroom-settings',
            __('Skyroom Settings', 'skyroom'),
            __('Settings', 'skyroom'),
            'manage_options'
        );
    }

    /**
     * Display setting page
     */
    function display()
    {
        // TODO display setting page
    }
}