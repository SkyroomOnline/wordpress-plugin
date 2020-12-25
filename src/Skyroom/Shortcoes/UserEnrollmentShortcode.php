<?php

namespace Skyroom\Shortcoes;

use Skyroom\Adapter\PluginAdapterInterface;
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
     * UserEnrollmentShortcode constructor
     *
     * @param PluginAdapterInterface $pluginAdapter
     * @param Viewer $viewer
     */
    public function __construct(PluginAdapterInterface $pluginAdapter, Viewer $viewer)
    {
        $this->pluginAdapter = $pluginAdapter;
        $this->viewer = $viewer;
    }

    public function display()
    {
        $user = wp_get_current_user();
        if ($user->exists()) {
            $context = [];
            $context['enrollments'] = $this->pluginAdapter->getUserEnrollments($user->ID);

            $this->viewer->view('enrollments.php', $context);
        } else {
            $this->viewer->view('login-form.php');
        }
    }
}
