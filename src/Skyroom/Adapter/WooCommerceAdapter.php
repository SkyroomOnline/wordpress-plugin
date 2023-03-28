<?php

namespace Skyroom\Adapter;

use DownShift\WordPress\EventEmitterInterface;
use Skyroom\Entity\Enrollment;
use Skyroom\Entity\Event;
use Skyroom\Entity\ProductWrapperInterface;
use Skyroom\Entity\WooCommerceProductWrapper;
use Skyroom\Factory\DICallableFactory;
use Skyroom\Repository\EventRepository;
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
    function getProducts($roomIds = null)
    {
        $query = [
            'post_type' => 'product',
            'tax_query' => [
                [
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'skyroom',
                ],
            ],
        ];

        if (!empty($roomIds)) {
            $query['meta_query'] = [
                [
                    'key' => '_skyroom_id',
                    'value' => $roomIds,
                    'compare' => 'IN',
                ],
            ];
        }

        $wpQuery = new \WP_Query($query);
        $wcProducts = array_map('wc_get_product', $wpQuery->get_posts());
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
            = "SELECT `product_skyroom_meta`.`meta_value` `room_id`, `order_customer_meta`.`meta_value` `user_id`, `order`.`ID` `order_id`,
                    `items`.`order_item_id` `item_id`
               FROM `$items` `items`
               INNER JOIN `$item_meta` `item_meta` ON `item_meta`.`order_item_id` = `items`.`order_item_id`
                    AND `item_meta`.`meta_key` = '_product_id'
               INNER JOIN `$wpdb->posts` `product` ON `product`.`ID` = `item_meta`.`meta_value`
               INNER JOIN `$wpdb->postmeta` `product_skyroom_meta` ON `product_skyroom_meta`.`post_id` = `product`.`ID`
                    AND `product_skyroom_meta`.`meta_key` = '_skyroom_id'
               INNER JOIN `$wpdb->term_relationships` `term_rel` ON `term_rel`.`term_taxonomy_id` = '$termId'
                    AND `term_rel`.`object_id` = `product`.`ID`
               INNER JOIN `$wpdb->posts` `order` ON `items`.`order_id` = `order`.`ID`
               INNER JOIN `$wpdb->postmeta` `order_customer_meta` ON `order_customer_meta`.`post_id` = `order`.`ID`
                    AND `order_customer_meta`.`meta_key` = '_customer_user'
               LEFT JOIN `$item_meta` `skyroom_synced_meta` ON `skyroom_synced_meta`.`meta_key` = '$skyroom_synced_meta_key'
                    AND `skyroom_synced_meta`.`order_item_id` = `items`.`order_item_id`
               INNER JOIN `$wpdb->users` `user` ON `user`.`id` = `order_customer_meta`.`meta_value`
               WHERE `order`.`post_status` IN ('wc-completed', 'wc-processing')
               AND `skyroom_synced_meta`.`order_item_id` IS NULL";

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * @inheritDoc
     */
    function setEnrollmentsSynced($itemIds)
    {
        foreach ($itemIds as $itemId) {
            try {
                wc_update_order_item_meta(
                    $itemId,
                    PluginAdapterInterface::SKYROOM_ENROLLMENT_SYNCED_META_KEY,
                    '1'
                );
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * @inheritDoc
     */
    function setEnrollmentSynced($userId, $roomId)
    {
        global $wpdb;

        $items = $wpdb->prefix . 'woocommerce_order_items';
        $itemMeta = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $skyroomIdMeta = PluginAdapterInterface::SKYROOM_ID_META_KEY;

        $query
            = "SELECT `item`.`id` AS `id`
               FROM `$items` `items`
               INNER JOIN `$itemMeta` `product_id_meta` ON `product_id_meta`.`meta_key` = '_product_id'
               INNER JOIN `$wpdb->posts` `product` ON `product`.`ID` = `product_id_meta`.`meta_value`
               INNER JOIN `$wpdb->postmeta` `product_skyroom_meta` ON `product_skyroom_meta`.`post_id` = `product`.`ID`
                   AND `product_skyroom_meta`.`meta_key` = $skyroomIdMeta
               INNER JOIN `$wpdb->posts` `order` ON `order`.`ID` = `item`.`order_id`
               INNER JOIN `$wpdb->postmeta` `order_customer_meta` ON `order_customer_meta`.`post_id` = `order`.`ID`
                   AND `order_customer_meta`.`meta_key` = '_customer_user'
                   AND `order_customer_meta`.`meta_value` = '$userId'
               AND `order`.`post_status` IN ('wc-completed', 'wc-processing')
               ORDER BY `order_date_meta`.`meta_value` DESC
        ";

        $items = $wpdb->get_results($query);

        try {
            foreach ($items as $item) {
                wc_update_order_item_meta(
                    $item->id,
                    PluginAdapterInterface::SKYROOM_ENROLLMENT_SYNCED_META_KEY,
                    true
                );
            }
        } catch (\Exception $exception) {
        }
    }

    /**
     * @inheritDoc
     */
    function getProductBySkyroomId($skyroomId)
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
            'meta_key' => '_skyroom_id',
            'meta_value' => $skyroomId
        ]);
        $posts = $query->get_posts();

        if (!empty($posts)) {
            return $this->wrapProduct(wc_get_product($posts[0]));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    function userBoughtProduct($userId, $product)
    {
        return wc_customer_bought_product(null, $userId, $product->getId());
    }

    /**
     * @inheritDoc
     */
    function getUserEnrollments($userId)
    {
        global $wpdb;

        $items = $wpdb->prefix . 'woocommerce_order_items';
        $item_meta = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $termId = get_term_by('slug', 'skyroom', 'product_type')->term_taxonomy_id;

        $query
            = "SELECT `product`.`id` AS `product_id`, `order_date_meta`.`meta_value` AS `enrollment_date`
               FROM `$wpdb->posts` `product`
               INNER JOIN `$wpdb->term_relationships` `term_rel` ON `term_rel`.`object_id` = `product`.`ID`
               INNER JOIN `$item_meta` `product_type_item_meta` ON `product_type_item_meta`.`meta_key` = '_product_id'
                    AND `product_type_item_meta`.`meta_value` = `product`.`ID`
               INNER JOIN `$items` `item` ON `item`.`order_item_id` = `product_type_item_meta`.`order_item_id`
               INNER JOIN `$wpdb->posts` `order` ON `order`.`ID` = `item`.`order_id`
                    AND `order`.`post_status` IN ('wc-completed', 'wc-processing')
               INNER JOIN `$wpdb->postmeta` `order_customer_meta` ON `order_customer_meta`.`post_id` = `order`.`ID`
               INNER JOIN `$wpdb->postmeta` `order_date_meta` ON `order_date_meta`.`meta_key` = '_completed_date'
                    AND `order_date_meta`.`post_id` = `order`.`ID`
               WHERE `term_rel`.`term_taxonomy_id` = '$termId'
               AND `product`.`post_status` = 'publish'
               AND `product_type_item_meta`.`order_item_id` = `item`.`order_item_id`
               AND `order_customer_meta`.`meta_key` = '_customer_user'
               AND `order_customer_meta`.`meta_value` = '$userId'
               ORDER BY `order_date_meta`.`meta_value` DESC";

        $enrolls = $wpdb->get_results($query);
        $products = array_reduce($enrolls, function ($array, $enroll) {
            $product = wc_get_product($enroll->product_id);
            $array[$product->get_id()] = $this->wrapProduct($product);
            return $array;
        }, []);

        return array_map(function ($enroll) use ($products) {
            return new Enrollment($products[$enroll->product_id], strtotime($enroll->enrollment_date));
        }, $enrolls);
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
     * @param EventRepository $eventRepository
     */
    function processOrder($orderId, $order, EventRepository $eventRepository)
    {
        $items = $order->get_items();
        $user = $order->get_user();
        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product->get_type() === 'skyroom') {
                // Add user to skyroom side room
                try {
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
     * @param Viewer $viewer
     */
    function addToCart(Viewer $viewer)
    {
        global $product;

        $context = [
            'product' => $product,
            'user_id' => get_current_user_id(),
        ];

        $viewer->view('woocommerce-add-to-cart.php', $context);
    }

    function validateAddToCart($prev, $productId)
    {
        if (empty($prev)) {
            return $prev;
        }

        $product = wc_get_product($productId);
        if (!$product || $product->get_type() !== 'skyroom') {
            return $prev;
        }

        return !$this->userBoughtProduct(get_current_user_id(), $this->wrapProduct($product));
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

    /**
     * @inheritDoc
     */
    public function purgeData()
    {
        global $wpdb;

        $postMeta = $wpdb->prefix . 'postmeta';
        $itemMeta = $wpdb->prefix . 'woocommerce_order_itemmeta';

        $wpdb->delete($postMeta, ['meta_key' => PluginAdapterInterface::SKYROOM_ID_META_KEY]);
        $wpdb->delete($itemMeta, ['meta_key' => PluginAdapterInterface::SKYROOM_ENROLLMENT_SYNCED_META_KEY]);
    }

    public function getOrderColumns()
    {
        return [
            'title' => __('Room title', 'skyroom'),
            'enter' => __('Enter room', 'skyroom'),
        ];
    }

}
