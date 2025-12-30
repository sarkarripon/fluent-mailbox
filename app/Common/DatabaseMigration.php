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
            cc longtext DEFAULT NULL,
            bcc longtext DEFAULT NULL,
            body longtext NOT NULL,
            attachments longtext DEFAULT NULL,
            status varchar(50) DEFAULT 'sent',
            is_read tinyint(1) DEFAULT 1,
            is_draft tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY is_draft (is_draft)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add missing columns to existing table
        self::addMissingColumns();
    }
    
    private static function addMissingColumns()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fluent_mailbox_emails';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        if (!$table_exists) {
            return;
        }
        
        // Get existing columns
        $columns = $wpdb->get_col("DESCRIBE $table");
        
        // Add missing columns
        $columns_to_add = [
            'cc' => "ALTER TABLE $table ADD COLUMN cc longtext DEFAULT NULL",
            'bcc' => "ALTER TABLE $table ADD COLUMN bcc longtext DEFAULT NULL",
            'attachments' => "ALTER TABLE $table ADD COLUMN attachments longtext DEFAULT NULL",
            'is_draft' => "ALTER TABLE $table ADD COLUMN is_draft tinyint(1) DEFAULT 0"
        ];
        
        foreach ($columns_to_add as $column_name => $sql) {
            if (!in_array($column_name, $columns)) {
                $wpdb->query($sql);
            }
        }
        
        // Add index for is_draft if it doesn't exist
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'is_draft'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table ADD INDEX is_draft (is_draft)");
        }
    }
}
