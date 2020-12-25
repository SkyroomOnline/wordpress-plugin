<?php

namespace Skyroom\Repository;

use Skyroom\Adapter\PluginAdapterInterface;
use Skyroom\Api\Client;
use Skyroom\Entity\User;
use Skyroom\Exception\BatchOperationFailedException;
use Skyroom\Exception\ConnectionNotEstablishedException;
use Skyroom\Exception\InvalidResponseStatusException;
use Skyroom\Exception\RequestFailedException;

/**
 * User Repository
 *
 * @package Skyroom\Repository
 */
class UserRepository
{
    const SKYROOM_ID_META_KEY = '_skyroom_id';

    /**
     * @var \wpdb $db
     */
    private $db;

    /**
     * User Repository constructor.
     *
     * @param \wpdb $db
     */
    public function __construct(\wpdb $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $limit
     * @param int $offset
     */
    public function getAllUsers($limit = 0, $offset = 0)
    {
        global $wpdb;

        $items = $wpdb->prefix . 'woocommerce_order_items';
        $item_meta = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $termId = get_term_by('slug', 'skyroom', 'product_type')->term_taxonomy_id;

        $query = "SELECT `user`.`display_name` `nickname`, `user`.`user_login` `username`, `product`.`post_title` `title`,
                    `product`.`id` `product_id`, `user`.`id` `user_id`
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
               INNER JOIN `$wpdb->users` `user` ON `user`.`id` = `order_customer_meta`.`meta_value`
               WHERE `order`.`post_status` IN ('wc-completed', 'wc-processing')";

        if (!empty($limit) && !empty($offset)) {
            $query = $this->db->prepare($query."LIMIT %d,%d", $limit, $offset);
        } elseif (!empty($limit)) {
            $query = $this->db->prepare($query."LIMIT %d", $limit);
        } else {
            $query = $query;
        }

        $users = $this->db->get_results($query, ARRAY_A);

        return $users;
    }

    /**
     * @return string|null
     */
    public function countAll()
    {
        global $wpdb;

        $items = $wpdb->prefix . 'woocommerce_order_items';
        $item_meta = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $termId = get_term_by('slug', 'skyroom', 'product_type')->term_taxonomy_id;

        $query = "SELECT COUNT(*) FROM `$items` `items`
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
               INNER JOIN `$wpdb->users` `user` ON `user`.`id` = `order_customer_meta`.`meta_value`
               WHERE `order`.`post_status` IN ('wc-completed', 'wc-processing')";

        return $this->db->get_var($query);
    }

}