<?php

namespace Skyroom\Shortcoes;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Repository\UserRepository;
use Skyroom\Util\Viewer;

/**
 * Shortcode for displaying user enrolls
 *
 * @package Skyroom\Shortcoes
 */
class UserEnrollmentShortcode
{
    /**
     * @var PluginAdapterInterface $pluginAdapter
     */
    private $pluginAdapter;

    /**
     * @var Viewer $viewer
     */
    private $viewer;

    /**
     * @var Client
     */
    private $client;

    /**
     * UserEnrollmentShortcode constructor
     *
     * @param PluginAdapterInterface $pluginAdapter
     * @param Viewer $viewer
     */
    public function __construct(PluginAdapterInterface $pluginAdapter, Viewer $viewer, Client $client)
    {
        $this->pluginAdapter = $pluginAdapter;
        $this->viewer = $viewer;
        $this->client = $client;
    }

    public function display()
    {
        $user = wp_get_current_user();
        $error = '';
        if ($user->exists()) {
            $url = null;
            $id = get_current_user_id();
            $user = get_userdata($id);
            $context = [];
            $enrolls['enrollments'] = $this->pluginAdapter->getUserEnrollments($user->ID);
            foreach ($enrolls['enrollments'] as $res) {
                $product = $res->getProduct();
                if ($product) {
                    $channelId = $product->getSkyroomId();
                    $params = [
                        'id' => strval($id),
                        'channelId' => intval($channelId),
                        'nickname' => $user->display_name,
                        'role' => "Normal"
                    ];
                    try {
                        $url = $this->client->request('getLoginUrl', $params);
                    } catch (\Exception $exception) {
                        $error = $exception->getMessage();
                    }

                    $context [] = [
                        'enrollments' => $res,
                        'url' => $url
                    ];

                }
            }
            if (!empty($error)) {
                $context = [
                    'error' => $error
                ];
            }

            $this->viewer->view('enrollments.php', $context);
        } else {
            $this->viewer->view('login-form.php');
        }
    }
}
