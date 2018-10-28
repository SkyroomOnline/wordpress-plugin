<?php

namespace Skyroom\Util;

/**
 * Plugin Activator
 *
 * @package Skyroom\Util
 */
class Activator
{
    const dbVersion = '1.1';

    /**
     * Activate plugin
     */
    public static function activate()
    {
        self::createTables();
    }

    /**
     * Create skyroom_enrolls database table
     */
    private static function createTables()
    {
        global $wpdb;

        $enrollsTable = $wpdb->prefix.'skyroom_enrolls';
        $eventsTable = $wpdb->prefix.'skyroom_events';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql
            = "CREATE TABLE $enrollsTable (
                   skyroom_user_id bigint(20) NOT NULL,
                   room_id bigint(20) NOT NULL,
                   user_id bigint(20) NOT NULL,
                   post_id bigint(20) NOT NULL,
                   synced boolean NOT NULL DEFAULT FALSE,
                   enroll_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                   PRIMARY KEY  (skyroom_user_id, room_id)
               ) $charsetCollate;
               
               CREATE TABLE $eventsTable (
                   id bigint(20) NOT NULL AUTO_INCREMENT,
                   title varchar(250) NOT NULL,
                   type smallint NOT NULL,
                   info text,
                   created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                   PRIMARY KEY  (id)                   
               ) $charsetCollate;";

        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('skyroom_db_version', self::dbVersion, true);
    }
}