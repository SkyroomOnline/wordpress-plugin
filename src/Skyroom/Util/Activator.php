<?php

namespace Skyroom\Util;

use DI\Container;

/**
 * Plugin Activator
 *
 * @package Skyroom\Util
 */
class Activator
{
    /**
     * Activate plugin
     */
    public static function activate()
    {
        self::createEnrollTable();
    }

    /**
     * Create skyroom_enrolls database table
     */
    private static function createEnrollTable()
    {
        global $wpdb;

        $tableName = $wpdb->prefix.'skyroom_enrolls';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql
            = "CREATE TABLE $tableName (
                   user_id bigint(20) NOT NULL,
                   post_id bigint(20) NOT NULL,
                   enroll_time datetime NOT NULL,
                   PRIMARY KEY  (user_id, post_id)
               ) $charsetCollate;";

        require ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}