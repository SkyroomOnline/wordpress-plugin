<?php

namespace Skyroom\Shortcoes;

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
     * @var UserRepository $roomRepository
     */
    private $userRepository;

    /**
     * @var Viewer $viewer
     */
    private $viewer;

    /**
     * UserEnrollmentShortcode constructor
     *
     * @param UserRepository $userRepository
     * @param Viewer         $viewer
     */
    public function __construct(UserRepository $userRepository, Viewer $viewer)
    {
        $this->userRepository = $userRepository;
        $this->viewer = $viewer;
    }

    public function display()
    {
        $user = wp_get_current_user();
        if ($user->exists()) {
            $context = [];
            $context['enrollments'] = $this->userRepository->getUserEnrollments($user->ID);

            $this->viewer->view('enrollments.php', $context);
        } else {
            $this->viewer->view('login-form.php');
        }
    }
}
