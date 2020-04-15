<?php

namespace Skyroom\Adapter;

use DownShift\WordPress\EventEmitterInterface;
use Skyroom\Entity\Event;
use Skyroom\Entity\ProductWrapperInterface;
use Skyroom\Entity\WooCommerceProductWrapper;
use Skyroom\Factory\DICallableFactory;
use Skyroom\Repository\EventRepository;
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
    )
    {
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

        // Skyroom products don't need processing status
        $this->eventEmitter->on('woocommerce_order_item_needs_processing', [$this, 'needsProcessing'], 10, 3);

        // Validate add to cart
        $this->eventEmitter->filter('woocommerce_add_to_cart_validation',
            $this->callableFactory->create([$this, 'validateAddToCart']), 10, 2);

        // Show skyroom items on order success
        $this->eventEmitter->filter('woocommerce_thankyou',
            $this->callableFactory->create([$this, 'skyroomThankyou']), 9, 1);
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
     * @inheritdoc
     */
    function getUnsyncedEnrolls()
    {
        global $wpdb;

        $items = $wpdb->prefix . 'woocommerce_order_items';
        $item_meta = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $termId = get_term_by('slug', 'skyroom', 'product_type')->term_taxonomy_id;
        $skyroom_synced_meta_key = PluginAdapterInterface::SKYROOM_ENROLLMENT_SYNCED_META_KEY;

        $query
            = "SELECT post_meta.meta_value room_id, order_customer_meta.meta_value user_id, _order.ID post_id, items.order_item_id item_id
               FROM $items items
               INNER JOIN $item_meta item_meta ON item_meta.order_item_id = items.order_item_id AND item_meta.meta_key = '_product_id'
               INNER JOIN $wpdb->posts posts ON posts.ID = item_meta.meta_value
               INNER JOIN $wpdb->postmeta post_meta ON post_meta.post_id = posts.ID AND post_meta.meta_key = '_skyroom_id'
               INNER JOIN $wpdb->term_relationships term_rel ON term_rel.object_id = posts.ID
               INNER JOIN $wpdb->posts _order ON items.order_id = _order.ID
               INNER JOIN $wpdb->postmeta order_customer_meta
                    ON order_customer_meta.post_id = _order.ID AND order_customer_meta.meta_key = '_customer_user'
               LEFT JOIN $item_meta skyroom_synced_meta
                    ON skyroom_synced_meta.order_item_id = items.order_item_id AND item_meta.meta_key = '$skyroom_synced_meta_key'
               WHERE term_rel.term_taxonomy_id = $termId
               AND _order.post_status = 'wc-completed'
               AND skyroom_synced_meta.order_item_id IS NULL";

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * @inheritDoc
     */
    function setEnrollmentsSynced($itemIds)
    {
        foreach ($itemIds as $itemId) {
            try {
                wc_add_order_item_meta($itemId, PluginAdapterInterface::SKYROOM_ENROLLMENT_SYNCED_META_KEY, '1');
            } catch (\Exception $e) {
            }
        }
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
     * @param int $orderId
     * @param \WC_Order $order
     * @param UserRepository $userRepository
     * @param EventRepository $eventRepository
     */
    function processOrder($orderId, $order, UserRepository $userRepository, EventRepository $eventRepository)
    {
        $items = $order->get_items();
        $user = $order->get_user();
        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product->get_type() === 'skyroom') {
                // First time user takes a product
                if (!$userRepository->isSkyroomUserCreated($user)) {
                    try {
                        $userRepository->addUser($user);
                        $info = [
                            'user_id' => $user->ID,
                        ];
                        $event = new Event(
                            sprintf(__('"%s" registered in skyroom service', 'skyroom'), $user->user_login),
                            Event::SUCCESSFUL,
                            $info
                        );
                        $eventRepository->save($event);
                    } catch (\Exception $exception) {
                        $info = [
                            'error_code' => $exception->getCode(),
                            'error_message' => $exception->getMessage(),
                            'user_id' => $user->ID,
                        ];
                        $event = new Event(
                            sprintf(__('Failed to register "%s" to skyroom service', 'skyroom'), $user->user_login),
                            Event::FAILED,
                            $info
                        );
                        $eventRepository->save($event);
                    }
                }

                // Add user to skyroom side room
                try {
                    // Creating skyroom user was not successful
                    if (!$userRepository->isSkyroomUserCreated($user)) {
                        continue;
                    }

                    $userRepository->addUserToRoom($user, $product->get_skyroom_id(), $orderId);

                    // Store event
                    $info = [
                        'order_id' => $orderId,
                        'item_id' => $product->get_id(),
                        'user_id' => $user->ID,
                        'room_id' => $product->get_skyroom_id(),
                    ];
                    $event = new Event(
                        sprintf(__('"%s" access given to "%s"', 'skyroom'), $product->get_room_title(), $user->user_login),
                        Event::SUCCESSFUL,
                        $info
                    );
                    $eventRepository->save($event);

                } catch (\Exception $exception) {
                    $info = [
                        'error_code' => $exception->getCode(),
                        'error_message' => $exception->getMessage(),
                        'order_id' => $orderId,
                        'item_id' => $product->get_id(),
                        'user_id' => $user->ID,
                        'room_id' => $product->get_skyroom_id(),
                    ];
                    $event = new Event(
                        sprintf(__('Failed to give "%s" access to "%s"', 'skyroom'), $product->get_room_title(), $user->user_login),
                        Event::FAILED,
                        $info
                    );
                    $eventRepository->save($event);
                }
            }
        }
    }

    /**
     * Filter order needs processing
     *
     * @param bool $needs
     * @param \WC_Product $product
     * @param int $orderId
     *
     * @return bool
     */
    function needsProcessing($needs, $product, $orderId)
    {
        return $product->get_type() === 'skyroom' ? false : $needs;
    }

    /**
     * Show add to cart button for user
     *
     * @param UserRepository $repository
     * @param Viewer $viewer
     */
    function addToCart(UserRepository $repository, Viewer $viewer)
    {
        global $product;

        $context = [
            'product' => $product,
            'purchased' => $repository->isUserInRoom(get_current_user_id(), $product->get_skyroom_id()),
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
    function skyroomThankyou($orderId)
    {
        $order = wc_get_order($orderId);
        $items = $order->get_items();
        $hasSkyroomProduct = false;
        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product && $product->get_type() === 'skyroom') {
                $hasSkyroomProduct = true;
                break;
            }
        }

        if ($hasSkyroomProduct) {
            $this->viewer->view('woocommerce-skyroom-order.php');
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
