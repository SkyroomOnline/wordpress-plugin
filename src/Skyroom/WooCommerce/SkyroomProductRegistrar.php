<?php

namespace Skyroom\WooCommerce;

use DI\Container;
use DI\Definition\Exception\DefinitionException;
use DI\DependencyException;
use DI\NotFoundException;
use DownShift\WordPress\EventEmitter;
use Skyroom\Api\Client;
use Skyroom\Exception\ConnectionTimeoutException;
use Skyroom\Exception\InvalidResponseException;

/**
 * Class SkyroomProductRegistrar
 *
 * @package Skyroom\WooCommerce
 */
class SkyroomProductRegistrar
{
    /**
     * @var Container $container
     */
    private $container;

    /**
     * SkyroomProductHooks constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register hooks
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function register()
    {
        $events = $this->container->get('Events');
        $DICallableFactory = $this->container->get('DICallableFactory');

        $events->filter('product_type_selector', [$this, 'registerProductType']);
        $events->filter('woocommerce_product_class', [$this, 'selectProductTypeClass'], 10, 2);
        $events->filter('woocommerce_product_data_tabs', [$this, 'registerSkyroomTab']);
        $events->filter('woocommerce_product_data_panels', [$this, 'showSkyroomTabContent']);
        $events->filter('woocommerce_product_data_tabs', [$this, 'hideUnneededTab']);
        $events->filter('woocommerce_process_product_meta_skyroom', $DICallableFactory->create([$this, 'processMeta']));
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
            return 'Skyroom\WooCommerce\SkyroomProduct';
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

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function showSkyroomTabContent()
    {
        global $post;

        $context = [
            'name' => get_post_meta($post->ID, '_skyroom_room_name') ?: '',
            'title' => get_post_meta($post->ID, '_skyroom_room_title') ?: '',
        ];
        $this->container->get('Viewer')->view('woocommerce-product-tab.php', $context);
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

        try {
            if (!empty($_POST['publish'])) {
                $id = $client->request(
                    'createRoom',
                    [
                        'name' => $name,
                        'title' => $title,
                    ]
                );

                update_post_meta($postId, '_skyroom_id', $id);
            } else {
                $id = get_post_meta($postId, '_skyroom_id');
                $client->request(
                    'updateRoom',
                    [
                        'room_id' => $id,
                        'name' => $name,
                        'title' => $title,
                    ]
                );
            }

            $room = $client->request('getRoom', ['room_id' => $id]);
            $totalSales = get_post_meta($postId, 'total_sales');

            update_post_meta($postId, '_skyroom_name', $room['name']);
            update_post_meta($postId, '_skyroom_title', $room['title']);
            update_post_meta($postId, '_skyroom_capacity', $capacity);
            update_post_meta($postId, '_stock', $capacity - $totalSales);
            update_post_meta($postId, '_manage_stock', true);

        } catch (ConnectionTimeoutException $e) {
            // TODO prevent publishing post
        } catch (InvalidResponseException $e) {
            // TODO prevent publishing post
        }
    }
}