<?php

namespace Skyroom\Menu;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Skyroom\Api\Client;
use Skyroom\Api\URL;
use Skyroom\Exception\ConnectionTimeoutException;
use Skyroom\Exception\InvalidResponseException;
use Skyroom\Util\Viewer;

/**
 * User submenu
 *
 * @package Skyroom\Menu
 */
class SettingSubmenu extends AbstractSubmenu
{
    /**
     * @var Container $container
     */
    private $container;

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var Viewer
     */
    private $viewer;

    /**
     * Setting submenu constructor
     *
     * @param Container $container
     * @param Client    $client
     */
    public function __construct(Container $container, Client $client, Viewer $viewer)
    {
        $this->container = $container;
        $this->client = $client;
        $this->viewer = $viewer;

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
        // Handle form submit
        if (isset($_POST['save'])) {
            $skyroomSiteUrl = $_POST['skyroom_site_url'];
            $skyroomApiKey = $_POST['skyroom_api_key'];
            $skyroomIntegratedPlugin = $_POST['skyroom_integrated_plugin'];

            // Change Client url object
            $URL = new URL($skyroomSiteUrl, $skyroomApiKey);
            $this->client->setURL($URL);

            try {
                $success = $this->client->request('ping');

                // Update wordpress options
                update_option('skyroom_site_url', $skyroomSiteUrl);
                update_option('skyroom_api_key', $skyroomApiKey);
                update_option('skyroom_integrated_plugin', $skyroomIntegratedPlugin);
            } catch (InvalidResponseException $exception) {
                switch ($exception->getCode()) {
                    case InvalidResponseException::INVALID_RESPONSE_STATUS:
                        $error
                            = __('Webservice ping failed (Invalid response code). Make sure you entered right site url.',
                            'skyroom');
                        break;

                    case InvalidResponseException::INVALID_RESPONSE_CONTENT:
                    case InvalidResponseException::INVALID_RESULT:
                        $error
                            = __('Webservice ping failed (Invalid response received). Make sure you entered right site url.',
                            'skyroom');
                        break;
                }
            } catch (ConnectionTimeoutException $exception) {
                $error
                    = __('Webservice ping failed (No response received). Make sure you entered right site url.',
                    'skyroom');
            }
        } else {
            $skyroomSiteUrl = get_option('skyroom_site_url');
            $skyroomApiKey = get_option('skyroom_api_key');
            $skyroomIntegratedPlugin = get_option('skyroom_integrated_plugin');
        }

        try {
            $context = [
                'pluginUrl' => $this->container->get('plugin.url'),
                'skyroomSiteUrl' => $skyroomSiteUrl,
                'skyroomApiKey' => $skyroomApiKey,
                'skyroomIntegratedPlugin' => $skyroomIntegratedPlugin
            ];
            $this->viewer->view('settings.php', $context);

        } catch (DependencyException $e) {
        } catch (NotFoundException $e) {
        }
    }
}