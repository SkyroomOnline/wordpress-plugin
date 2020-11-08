<?php

namespace Skyroom\Controller;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Entity\Event;
use Skyroom\Entity\WooCommerceProductWrapper;
use Skyroom\Repository\EventRepository;
use Skyroom\Repository\UserRepository;

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
     * @var UserRepository
     */
    private $userRepository;

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
        UserRepository $userRepository,
        EventRepository $eventRepository
    )
    {
        $this->client = $client;
        $this->pluginAdapter = $pluginAdapter;
        $this->userRepository = $userRepository;
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
        if ($matches) {
            $product = wc_get_product($matches['id']);
            if($product->get_type() === "skyroom") {
                $product = $this->pluginAdapter->wrapProduct($product);

                $bought = $this->pluginAdapter->userBoughtProduct(get_current_user_id(), $product);

                if (!$bought) {
                    wp_die(__('You should buy this course before logging into class'));
                }

                $skyroomRoomId = $product->getSkyroomId();

                try {
                    $userData = wp_get_current_user();

                    $username = $userData->data->user_login;
                    $nickname = $userData->data->display_name;

                    $url = $this->client->request('createLoginUrl', [
                        'room_id' => $skyroomRoomId,
                        'user_id' => $username,
                        'nickname' => $nickname,
                        'access' => 1,
                        'ttl' => 60,
                    ]);

                    wp_redirect($url);
                    exit;

                } catch (\Exception $exception) {
                    // Save error event
                    $info = [
                        'error_code' => $exception->getCode(),
                        'error_message' => $exception->getMessage(),
                        'user_id' => get_current_user_id(),
                        'skyroom_user_id' => $this->userRepository->getSkyroomId(get_current_user_id()),
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
            }else{
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
