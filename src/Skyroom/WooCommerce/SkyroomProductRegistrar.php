<?php

namespace Skyroom\WooCommerce;

use DownShift\WordPress\EventEmitter;
use DownShift\WordPress\EventEmitterInterface;
use Skyroom\Api\Client;
use Skyroom\Factory\DICallableFactory;
use Skyroom\Util\Viewer;

/**
 * Class SkyroomProductRegistrar
 *
 * @package Skyroom\WooCommerce
 */
class SkyroomProductRegistrar
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
     * @var Viewer
     */
    private $viewer;

    /**
     * @var \WP_Error
     */
    private $error;

    public function __construct(
        EventEmitterInterface $eventEmitter,
        DICallableFactory $callableFactory,
        Viewer $viewer
    ) {
        $this->eventEmitter = $eventEmitter;
        $this->callableFactory = $callableFactory;
        $this->viewer = $viewer;
        $this->error = $this->getErrorTransient();
    }

    /**
     * Register hooks
     */
    public function register()
    {
        $this->eventEmitter->filter('product_type_selector', [$this, 'registerProductType']);
        $this->eventEmitter->filter('woocommerce_product_class', [$this, 'selectProductTypeClass'], 10, 2);
        $this->eventEmitter->filter('woocommerce_product_data_tabs', [$this, 'registerSkyroomTab']);
        $this->eventEmitter->filter('woocommerce_product_data_panels', [$this, 'showSkyroomTabContent']);
        $this->eventEmitter->filter('woocommerce_product_data_tabs', [$this, 'hideUnneededTab']);
        $this->eventEmitter->filter('woocommerce_process_product_meta_skyroom',
            $this->callableFactory->create([$this, 'processMeta']), 10, 1);
        $this->eventEmitter->on('admin_notices', [$this, 'warnSavePostFail'], 20);
        $this->eventEmitter->on('post_updated_messages', [$this, 'removePublishMessage'], 11);
    }

    /**
     * @param array $types
     *
     * @return array
     */
    public function registerProductType($types)
    {
        $types['skyroom'] = __('Skyroom', 'skyroom');

        return $types;
    }

    /**
     * @param string $className
     * @param string $productType
     *
     * @return string
     */
    public function selectProductTypeClass($className, $productType)
    {
        if ($productType === 'skyroom') {
            return SkyroomProduct::class;
        }

        return $className;
    }

    /**
     * @param array $tabs
     *
     * @return array
     */
    public function registerSkyroomTab($tabs)
    {
        $tabs['skyroom'] = [
            'label' => __('Skyroom', 'skyroom'),
            'target' => 'skyroom_data',
            'class' => 'show_if_skyroom',
            'priority' => 12,
        ];

        return $tabs;
    }

    public function showSkyroomTabContent()
    {
        global $post;

        $context = [
            'name' => get_post_meta($post->ID, '_skyroom_name', true) ?: '',
            'title' => get_post_meta($post->ID, '_skyroom_title', true) ?: '',
            'capacity' => get_post_meta($post->ID, '_skyroom_capacity', true) ?: '',
        ];
        $this->viewer->view('woocommerce-product-tab.php', $context);
    }

    public function hideUnneededTab($tabs)
    {
        $tabs['shipping']['class'][] = 'hide_if_skyroom';

        return $tabs;
    }

    /**
     * @param integer $postId
     * @param Client  $client
     */
    public function processMeta($postId, Client $client)
    {
        $name = $_POST['_skyroom_name'] ?: $_POST['post_name'];
        $title = $_POST['_skyroom_title'] ?: $_POST['post_title'];
        $capacity = $_POST['_skyroom_capacity'] ?: null;

        $product = wc_get_product($postId);
        $skyroomId = $product->get_skyroom_id();

        $update = !empty($skyroomId);

        try {
            if (!$update) {
                $skyroomId = $client->request(
                    'createRoom',
                    [
                        'name' => $name,
                        'title' => $title,
                        'op_login_first' => true,
                    ]
                );

                update_post_meta($postId, '_skyroom_id', $skyroomId);
            } else {
                $client->request(
                    'updateRoom',
                    [
                        'room_id' => (int)$skyroomId,
                        'name' => $name,
                        'title' => $title,
                    ]
                );
            }

            $room = $client->request('getRoom', ['room_id' => (int)$skyroomId]);
            $totalSales = get_post_meta($postId, 'total_sales', true);

            update_post_meta($postId, '_skyroom_name', $room->name);
            update_post_meta($postId, '_skyroom_title', $room->title);
            update_post_meta($postId, '_skyroom_capacity', $capacity);
            update_post_meta($postId, '_stock', $capacity - $totalSales);
            update_post_meta($postId, '_manage_stock', 'yes');

        } catch (\Exception $e) {
            if (empty($skyroomId)) {
                $this->revertPostPublish($postId);
            }

            $error = new \WP_Error($e->getCode(), $e->getMessage());
            $userId = get_current_user_id();
            set_transient("skyroom_save_product_error_{$postId}_{$userId}", $error, 45);
        }
    }

    /**
     * Revert post status if create/update room failed
     *
     * @param int $postId
     */
    public function revertPostPublish($postId)
    {
        global $wpdb;

        $post = get_post($postId);
        if ($post->post_status === 'publish') {
            $wpdb->update($wpdb->posts, ['post_status' => 'draft'], ['ID' => $post->ID]);
        }
    }

    public function warnSavePostFail()
    {
        if ($this->error) {
            echo '<div id="skyroom_errors" class="error notice is-dismissible">';
            echo '<h3>'.__('Error', 'skyroom').'</h3>';
            echo '<p>'.$this->error->get_error_message().'</p>';
            echo '</div>';
        }
    }

    public function removePublishMessage($messages)
    {
        if ($this->error) {
            $messages['product'] = [];
        }

        return $messages;
    }

    /**
     * @return \WP_Error
     */
    public function getErrorTransient()
    {
        if ($GLOBALS['pagenow'] !== 'post.php' || empty($_GET['post'])) {
            return null;
        }

        $postId = $_GET['post'];
        $userId = get_current_user_id();
        if ($error = get_transient("skyroom_save_product_error_{$postId}_{$userId}")) {
            delete_transient("skyroom_save_product_error_{$postId}_{$userId}");
            return $error;
        }

        return null;
    }
}
