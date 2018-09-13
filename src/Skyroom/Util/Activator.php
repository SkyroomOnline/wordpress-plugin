<?php

namespace Skyroom\Util;

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

        $enrollsTable = $wpdb->prefix.'skyroom_enrolls';
        $eventsTable = $wpdb->prefix.'skyroom_events';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql
            = "CREATE TABLE $enrollsTable (
                   user_id bigint(20) NOT NULL,
                   room_id bigint(20) NOT NULL,
                   post_id bigint(20) NOT NULL,
                   enroll_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                   PRIMARY KEY  (user_id, room_id)
               ) $charsetCollate;
               
               CREATE TABLE $eventsTable (
                   id bigint(20) NOT NULL AUTO_INCREMENT,
                   title varchar(250) NOT NULL,
                   type smallint NOT NULL,
                   info text,
                   created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                   PRIMARY KEY  (id)                   
               ) $charsetCollate;";

        require ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}