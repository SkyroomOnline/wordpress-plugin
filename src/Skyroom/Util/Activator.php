<?php

namespace Skyroom\Util;

/**
 * Plugin Activator
 *
 * @package Skyroom\Util
 */
class Activator
{
    const dbVersion = '1.2';

    /**
     * Activate plugin
     */
    public static function activate()
    {
        self::createTables();
    }

    /**
     * Create skyroom_events database table
     */
    private static function createTables()
    {
        global $wpdb;

        $eventsTable = $wpdb->prefix.'skyroom_events';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql
            = "CREATE TABLE $eventsTable (
                   id bigint(20) NOT NULL AUTO_INCREMENT,
                   title varchar(250) NOT NULL,
                   type smallint NOT NULL,
                   info text,
                   created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                   PRIMARY KEY  (id)                   
               ) $charsetCollate;";

        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Remove skyroom_enrolls table if exists
        $enrollsTable = $wpdb->prefix.'skyroom_enrolls';
        $wpdb->query("DROP TABLE IF EXISTS $enrollsTable");

        update_option('skyroom_db_version', self::dbVersion, true);
    }
}