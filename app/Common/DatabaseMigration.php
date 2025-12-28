<?php

namespace FluentMailbox\Common;

class DatabaseMigration
{
    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'fluent_mailbox_emails';

        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            message_id varchar(255) DEFAULT '',
            subject varchar(255) NOT NULL,
            sender varchar(255) NOT NULL,
            recipients longtext NOT NULL,
            body longtext NOT NULL,
            status varchar(50) DEFAULT 'sent',
            is_read tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
