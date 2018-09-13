<?php

namespace Skyroom\Adapter;

use DownShift\WordPress\EventEmitterInterface;
use Skyroom\Entity\ProductWrapperInterface;
use Skyroom\Entity\WooCommerceProductWrapper;
use Skyroom\Factory\DICallableFactory;
use Skyroom\Repository\UserRepository;
use Skyroom\Util\Viewer;
use Skyroom\WooCommerce\SkyroomProduct;
use Skyroom\WooCommerce\SkyroomProductRegistrar;

/**
 * WooCommerce adapter
 *
 * @package Skyroom\Adapter
 */
class WooCommerceAdapter implements PluginAdapterInterface
{
    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    /**
     * @var DICallableFactory
     */
    private $callableFactory;

    /**
     * @var SkyroomProductRegistrar $productRegistrar
     */
    private $productRegistrar;

    /**
     * @var Viewer
     */
    private $viewer;

    public function __construct(
        EventEmitterInterface $eventEmitter,
        DICallableFactory $callableFactory,
        SkyroomProductRegistrar $productRegistrar,
        Viewer $viewer
    ) {
        $this->eventEmitter = $eventEmitter;
        $this->callableFactory = $callableFactory;
        $this->productRegistrar = $productRegistrar;
        $this->viewer = $viewer;
    }

    /**
     * Setup WooCommerce adapter
     */
    function setup()
    {
        // Register custom product type
        $this->productRegistrar->register();

        // Show add-to-card btn
        $this->eventEmitter->on('woocommerce_skyroom_add_to_cart',
            $this->callableFactory->create([$this, 'addToCart']), 10, 0);

        // Register order completed hook
        $this->eventEmitter->on('woocommerce_order_status_completed',
            $this->callableFactory->create([$this, 'processOrder']), 10, 2);

        // Validate add to cart
        $this->eventEmitter->filter('woocommerce_add_to_cart_validation',
            $this->callableFactory->create([$this, 'validateAddToCart']), 10, 2);

        // Show skyroom items on order success
        $this->eventEmitter->filter('woocommerce_thankyou',
            $this->callableFactory->create([$this, 'showSkyroomItems']), 9, 1);
    }

    /**
     * Get WooCommerce products that linked to skyroom rooms
     *
     * @param array $roomIds
     *
     * @return WooCommerceProductWrapper[]
     */
    function getProducts($roomIds)
    {
        $query = new \WP_Query([
            'post_type' => 'product',
            'tax_query' => [
                [
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'skyroom',
                ],
            ],
            'meta_query' => [
                [
                    'key' => '_skyroom_id',
                    'value' => $roomIds,
                    'compare' => 'IN',
                ],
            ],
        ]);

        $wcProducts = array_map('wc_get_product', $query->get_posts());
        $products = array_map([$this, 'wrapProduct'], $wcProducts);

        return $products;
    }

    /**
     * Wraps WooCommerce product in ProductWrapperInterface instance
     *
     * @param SkyroomProduct $product
     *
     * @return ProductWrapperInterface
     */
    function wrapProduct($product)
    {
        return new WooCommerceProductWrapper($product);
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
     */
    function processOrder($orderId, $order, UserRepository $repository)
    {
        $items = $order->get_items();
        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product->get_type() === 'skyroom') {
                try {
                    $repository->addUserToRoom(wp_get_current_user(), $product->get_skyroom_id(), $orderId);
                } catch (\Exception $exception) {

                }
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
        global $product;

        $context = [
            'product' => $product,
            'purchased' => $repository->isUserInRoom(get_current_user_id(), $product->id),
        ];
        $viewer->view('woocommerce-add-to-cart.php', $context);
    }

    function validateAddToCart($prev, $productId, UserRepository $repository)
    {
        if (empty($prev)) {
            return $prev;
        }

        $product = wc_get_product($productId);
        if ($product->get_type() !== 'skyroom') {
            return $prev;
        }

        return !$repository->isUserInRoom(get_current_user_id(), get_post_meta($productId, '_skyroom_id', true));
    }

    /**
     * @param $orderId
     */
    function showSkyroomItems($orderId)
    {
        $order = wc_get_order($orderId);
        $items = $order->get_items();
        $skyroomProducts = [];
        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product && $product->get_type() === 'skyroom') {
                $skyroomProducts[] = $product;
            }
        }

        if (!empty($skyroomProducts)) {
            $this->viewer->view(
                'woocommerce-skyroom-order.php',
                ['products' => $skyroomProducts, 'columns' => $this->getOrderColumns()]
            );
        }
    }

    public function getOrderColumns()
    {
        return [
            'title' => __('Room title', 'skyroom'),
            'enter' => __('Enter room', 'skyroom'),
        ];
    }
}
