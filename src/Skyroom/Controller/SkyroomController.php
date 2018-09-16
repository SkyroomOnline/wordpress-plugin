<?php

namespace Skyroom\Controller;

use Skyroom\Api\Client;

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

    public function __construct(Client $client)
    {
        $this->client = $client;
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
        if ($matches = $this->matchRequestPath()) {
            global $wpdb;
            $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}skyroom_enrolls WHERE user_id=%s AND room_id=%s",
                get_current_user_id(), $matches['id']);
            $enrollment = $wpdb->get_row($query);

            if (empty($enrollment)) {
                $wp->handle_404();

                return false;
            }

            try {
                $url = $this->client->request('getLoginUrl', [
                    'room_id' => $enrollment['room_id'],
                    'user_id' => $enrollment['user_id'],
                    'ttl' => 60,
                ]);

                wp_redirect($url);

            } catch (\Exception $exception) {
                echo '<h1>'.__('Error').'</h1>';
                echo '<p>'.__('A fatal error occurred. Please contact site support to fix problem.').'</p>';
            }

            return false;
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