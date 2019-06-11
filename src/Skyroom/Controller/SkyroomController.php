<?php

namespace Skyroom\Controller;

use Skyroom\Api\Client;
use Skyroom\Entity\Event;
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
     * @var Client
     */
    private $client;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    public function __construct(Client $client, EventRepository $eventRepository)
    {
        $this->client = $client;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Parse request
     *
     * @param bool $do
     * @param \WP  $wp
     *
     * @return bool
     */
    function parseRequest($do, $wp)
    {
        $matches = $this->matchRequestPath();
        if ($matches) {
            global $wpdb;
            $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}skyroom_enrolls WHERE user_id=%s AND room_id=%s",
                get_current_user_id(), $matches['id']);
            $enrollment = $wpdb->get_row($query);

            if (empty($enrollment)) {
                return true;
            }

            try {
                $url = $this->client->request('getLoginUrl', [
                    'room_id' => $enrollment->room_id,
                    'user_id' => $enrollment->skyroom_user_id,
                    'ttl' => 60,
                ]);

                wp_redirect($url);
                exit;

            } catch (\Exception $exception) {
                // Save error event
                $info = [
                    'error_code' => $exception->getCode(),
                    'error_message' => $exception->getMessage(),
                    'user_id' => $enrollment->user_id,
                    'room_id' => $enrollment->room_id,
                ];
                $event = new Event(
                    sprintf(__('Redirecting "%s" to classroom failed', 'skyroom'), wp_get_current_user()->user_login),
                    Event::FAILED,
                    $info
                );
                $this->eventRepository->save($event);

                $title = __('Error entering class', 'skyroom');
                $message = __('There was an error communicating with class holder. Please contact site support to fix problem.', 'skyroom');
                wp_die('<h1>'.$title.'</h1>'.'<p>'.$message.'</p>', $title);
            }

            return true;
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