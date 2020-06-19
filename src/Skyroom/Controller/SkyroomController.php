<?php

namespace Skyroom\Controller;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Entity\Event;
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
            $product = $this->pluginAdapter->getProductBySkyroomId($matches['id']);
            $bought = $this->pluginAdapter->userBoughtProduct(get_current_user_id(), $product);

            if (!$bought) {
                wp_die(__('You should buy this course before logging into class'));
            }

            try {
                $this->userRepository->ensureSkyroomUserAdded(wp_get_current_user());
                $skyroomUserId = $this->userRepository->getSkyroomId(get_current_user_id());
                $skyroomRoomId = $product->getSkyroomId();

                $url = $this->client->request('getLoginUrl', [
                    'room_id' => $skyroomRoomId,
                    'user_id' => $skyroomUserId,
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
                    'room_id' => $product->getSkyroomId(),
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
