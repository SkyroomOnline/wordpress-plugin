<?php

namespace Skyroom\Adapter;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Skyroom\Exception\ConnectionTimeoutException;
use Skyroom\Exception\InvalidResponseException;
use Skyroom\Repository\UserRepository;
use Skyroom\Util\Viewer;
use Skyroom\WooCommerce\SkyroomProductRegistrar;

/**
 * WooCommerce adapter
 *
 * @package Skyroom\Adapter
 */
class WooCommerceAdapter implements PluginAdapterInterface
{
    /**
     * @var Container $container
     */
    private $container;

    /**
     * WooCommerce Adapter constructor
     *
     * @param Container $container
     */
    function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Setup WooCommerce adapter
     *
     * @throws DependencyException
     * @throws NotFoundException
     *
     * @return mixed
     */
    function setup()
    {
        // Register custom product type
        $registrar = new SkyroomProductRegistrar($this->container);
        $registrar->register();

        // Show add-to-card btn
        $this->container->get('Events')->on('woocommerce_skyroom_add_to_cart',
            $this->container->make('Skyroom\Util\DICallable',
                ['callable' => [$this, 'addToCart']]), 10, 0);

        // Register order completed hook
        $this->container->get('Events')->on('woocommerce_order_status_completed',
            $this->container->make('Skyroom\Util\DICallable',
                ['callable' => [$this, 'processOrder']]), 10, 2);

        // Validate add to cart
        $this->container->get('Events')->filter('woocommerce_add_to_cart_validation',
            $this->container->make('Skyroom\Util\DICallable',
                ['callable' => [$this, 'validateAddToCart']]), 10, 2);
    }

    /**
     * Get WooCommerce products that linked to skyroom rooms
     *
     * @param array $roomIds
     *
     * @return mixed
     */
    function getProducts($roomIds)
    {
        // TODO: Implement getProducts() method.
    }

    /**
     * Get Product singular or plural form
     *
     * @param bool $plural Whether to get plural or singular form
     *
     * @return string
     */
    function getPostString($plural = false)
    {
        return $plural ? __('Products', 'skyroom') : __('Product', 'skyroom');
    }

    /**
     * @param int            $orderId
     * @param \WC_Order      $order
     * @param UserRepository $repository
     *
     * @throws ConnectionTimeoutException
     * @throws InvalidResponseException
     */
    function processOrder($orderId, $order, UserRepository $repository)
    {
        $items = $order->get_items();
        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product->get_type() === 'skyroom') {
                $repository->addUserToRoom(wp_get_current_user(), $product->get_skyroom_id(), $orderId);
            }
        }
    }

    /**
     * Show add to cart button for user
     *
     * @param UserRepository $repository
     * @param Viewer         $viewer
     */
    function addToCart(UserRepository $repository, Viewer $viewer)
    {
        global $post;

        $purchased = $repository->isUserInRoom(get_current_user_id(), $post->id);
        $viewer->view('woocommerce-add-to-cart.php');
    }

    function validateAddToCart($prev, $productId, $quantity, UserRepository $repository)
    {
        if (!$prev) {
            return $prev;
        }

        return !$repository->isUserInRoom(get_current_user_id(), get_post_meta($productId, '_skyroom_id'));
    }
}
