<?php

namespace Skyroom\Controller;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Entity\Event;
use Skyroom\Entity\WooCommerceProductWrapper;
use Skyroom\Repository\EventRepository;

/**
 * Class Skyroom
 *
 * @package Skyroom\Controller
 */
class SkyroomController
{
    const REDIRECT_SKYROOM_PATH = '#^redirect-to-room/(?<id>\d+)$#';

    /**
     * @var PluginAdapterInterface
     */
    private $pluginAdapter;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    public function __construct(
        Client $client,
        PluginAdapterInterface $pluginAdapter,
        EventRepository $eventRepository
    )
    {
        $this->client = $client;
        $this->pluginAdapter = $pluginAdapter;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Parse request
     *
     * @param bool $do
     * @param \WP $wp
     *
     * @return bool
     */
    function parseRequest($do, $wp)
    {
        $matches = $this->matchRequestPath();
        $product_id = $matches['id'];
        if ($matches) {
            $product = wc_get_product($matches['id']);
            if ($product->get_type() === "skyroom") {
                $product = $this->pluginAdapter->wrapProduct($product);

                $bought = $this->pluginAdapter->userBoughtProduct(get_current_user_id(), $product);

                if (!$bought) {
                    wp_die(__('You should buy this course before logging into class'));
                }

                $skyroomRoomId = $product->getSkyroomId();

                try {
                    $userData = wp_get_current_user();

                    $userId = $userData->data->ID;
                    $nickname = $userData->data->display_name;

                    $ttl = get_option('skyroom_link_ttl');
                    $ttlUnit = get_option('skyroom_link_ttl_unit');
                    if (!$ttlUnit) {
                        $ttlUnit = 1;
                    } elseif ($ttlUnit == 'sec') {
                        $ttlUnit = 1;
                    } elseif ($ttlUnit == 'min') {
                        $ttlUnit = 60;
                    }
                    if (!$ttl) {
                        $ttl = 60;
                    }
                    $ttl = $ttl * $ttlUnit;

                    $user_data = get_user_meta($userId, '_skyroom_access', true);
                    $access_level = 1;

                    if ($user_data) {
                        $accesses = unserialize($user_data);
                        foreach ($accesses as $access) {
                            if ($access['product_id'] == $product_id) {
                                $access_level = $access['access_level'];
                            }
                        }
                    }

                    $url = $this->client->request('createLoginUrl', [
                        'room_id' => (int)$skyroomRoomId,
                        'user_id' => $userId,
                        'nickname' => $nickname,
                        'access' => (int)$access_level,
                        'concurrent' => 1,
                        'ttl' => (int)$ttl,
                    ]);

                    wp_redirect($url);
                    exit;

                } catch (\Exception $exception) {
                    // Save error event
                    $info = [
                        'error_code' => $exception->getCode(),
                        'error_message' => $exception->getMessage(),
                        'user_id' => get_current_user_id(),
                        'skyroom_user_id' => "-",
                        'room_id' => $skyroomRoomId,
                    ];
                    $event = new Event(
                        sprintf(__('Redirecting "%s" to classroom failed', 'skyroom'), wp_get_current_user()->user_login),
                        Event::FAILED,
                        $info
                    );
                    $this->eventRepository->save($event);

                    $title = __('Error entering class', 'skyroom');
                    $message = __('Seems there is a problem on our side. Please contact support to resolve issue.', 'skyroom');
                    wp_die('<h1>' . $title . '</h1>' . '<p>' . $message . '</p>', $title);
                }
            } else {
                $title = __('Error product type', 'skyroom');
                $message = __('This product is not skyroom type.', 'skyroom');
                wp_die('<h1>' . $title . '</h1>' . '<p>' . $message . '</p>', $title);
            }
        }

        return $do;
    }

    /**
     * @return int
     */
    public function matchRequestPath()
    {
        $path = trim($this->getPathInfo(), '/');
        $found = preg_match(self::REDIRECT_SKYROOM_PATH, $path, $matches);
        if ($found) {
            return $matches;
        }

        return 0;
    }

    /**
     * @return string
     */
    private function getPathInfo()
    {
        $home_path = parse_url(home_url(), PHP_URL_PATH);
        return preg_replace("#^/?{$home_path}/#", '/', add_query_arg(array()));
    }
}
