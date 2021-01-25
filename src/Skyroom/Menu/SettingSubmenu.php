<?php

namespace Skyroom\Menu;

use Skyroom\Api\Client;
use Skyroom\Api\URL;
use Skyroom\Util\Viewer;

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
     * @var Viewer
     */
    private $viewer;

    /**
     * Setting submenu constructor
     *
     * @param Client $client
     * @param Viewer $viewer
     */
    public function __construct(Client $client, Viewer $viewer)
    {
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
        // initialize vars
        $error = '';
        $success = null;

        // Handle form submit
        if (isset($_POST['save'])) {
            $skyroomSiteUrl = $_POST['skyroom_site_url'];
            $skyroomApiKey = $_POST['skyroom_api_key'];
            $skyroomLinkTtl = $_POST['skyroom_link_ttl'];
            $skyroomLinkTtlUnit = $_POST['skyroom_link_ttl_unit'];
            $skyroomIntegratedPlugin = $_POST['skyroom_integrated_plugin'];

            // Change Client url object
            $URL = new URL($skyroomSiteUrl, $skyroomApiKey);
            $this->client->setURL($URL);

            try {
                $success = $this->client->request('ping');

                // Update wordpress options
                update_option('skyroom_site_url', $skyroomSiteUrl);
                update_option('skyroom_api_key', $skyroomApiKey);
                update_option('skyroom_link_ttl', $skyroomLinkTtl);
                update_option('skyroom_link_ttl_unit', $skyroomLinkTtlUnit);
                update_option('skyroom_integrated_plugin', $skyroomIntegratedPlugin);
            } catch (\Exception $exception) {
                $error = $exception->getMessage();
            }

        } else {
            $skyroomSiteUrl = get_option('skyroom_site_url');
            $skyroomApiKey = get_option('skyroom_api_key');
            $skyroomLinkTtl = get_option('skyroom_link_ttl');
            $skyroomLinkTtlUnit = get_option('skyroom_link_ttl_unit');
            $skyroomIntegratedPlugin = get_option('skyroom_integrated_plugin');
        }

        if(!$skyroomLinkTtlUnit){
            $skyroomLinkTtlUnit = 'sec';
        }

        $context = [
            'error' => $error,
            'success' => $success,
            'skyroomSiteUrl' => $skyroomSiteUrl,
            'skyroomApiKey' => $skyroomApiKey,
            'skyroomLinkTtl' => $skyroomLinkTtl,
            'skyroomLinkTtlUnit' => $skyroomLinkTtlUnit,
            'skyroomIntegratedPlugin' => $skyroomIntegratedPlugin,
        ];
        $this->viewer->view('settings.php', $context);
    }
}
