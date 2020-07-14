<?php


namespace Skyroom\WooCommerce;

use DownShift\WordPress\EventEmitterInterface;
use Skyroom\Util\Viewer;


class SkyroomWooMyAccount
{
    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    public function __construct(
        EventEmitterInterface $eventEmitter,
        Viewer $viewer
    )
    {
        $this->eventEmitter = $eventEmitter;
        $this->viewer = $viewer;
    }

    /**
     * Setup hooks
     */
    public function setup()
    {
        $this->eventEmitter->filter('init', [$this, 'my_custom_endpoints']);
        $this->eventEmitter->filter('query_vars', [$this, 'sk_custom_menu_vars']);
        $this->eventEmitter->filter('wp_loaded', [$this, 'sk_custom_menu']);
        $this->eventEmitter->filter('woocommerce_account_menu_items', [$this, 'my_custom_my_account_menu_items'], 99, 1);
        $this->eventEmitter->filter('woocommerce_account_my-courses_endpoint', [$this, 'my_custom_endpoint_content']);
    }

    public function my_custom_endpoints()
    {
        add_rewrite_endpoint('my-courses', EP_ROOT | EP_PAGES);
    }

    /**
     * @param $vars
     *
     * @return string
     * */
    public function sk_custom_menu_vars($vars)
    {
        $vars[] = 'my-courses';

        return $vars;
    }

    public function sk_custom_menu()
    {
        flush_rewrite_rules();
    }

    /**
     * @param $items
     *
     * @return mixed
     */

    public function my_custom_my_account_menu_items($items)
    {
        $my_items = array(
            'my-courses' => __( 'Enrolled Courses', 'skyroom' ),
        );

        $my_items = array_slice( $items, 0, 1, true ) +
            $my_items +
            array_slice( $items, 1, count( $items ), true );

        return $my_items;
    }

    /**
     *
     * endpoint contents
     */
    function my_custom_endpoint_content()
    {
        echo do_shortcode( '[SkyroomEnrollments]' );
    }
}