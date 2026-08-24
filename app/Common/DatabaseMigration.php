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
            workflow_status varchar(20) DEFAULT 'open',
            assigned_to bigint(20) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 1,
            is_draft tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY workflow_status (workflow_status),
            KEY assigned_to (assigned_to),
            KEY is_draft (is_draft)
        ) $charsetCollate;";

        $notesTable = $wpdb->prefix . 'fluent_mailbox_email_notes';
        $notesSql = "CREATE TABLE $notesTable (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            note longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email_id (email_id),
            KEY user_id (user_id)
        ) $charsetCollate;";

        $tagsTable = $wpdb->prefix . 'fluent_mailbox_tags';
        $tagsSql = "CREATE TABLE $tagsTable (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            color varchar(20) DEFAULT '#3B82F6',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charsetCollate;";

        $emailTagsTable = $wpdb->prefix . 'fluent_mailbox_email_tags';
        $emailTagsSql = "CREATE TABLE $emailTagsTable (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email_id bigint(20) NOT NULL,
            tag_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email_id (email_id),
            KEY tag_id (tag_id),
            UNIQUE KEY email_tag (email_id, tag_id)
        ) $charsetCollate;";

        $mailboxesTable = $wpdb->prefix . 'fluent_mailbox_mailboxes';
        $mailboxesSql = "CREATE TABLE $mailboxesTable (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            from_name varchar(255) DEFAULT '',
            driver varchar(50) NOT NULL DEFAULT 'imap',
            driver_settings longtext,
            category varchar(50) DEFAULT 'other',
            color varchar(20) DEFAULT '#3B82F6',
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 0,
            last_synced_at datetime DEFAULT NULL,
            sync_state longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY driver (driver),
            KEY is_active (is_active)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($notesSql);
        dbDelta($tagsSql);
        dbDelta($emailTagsSql);
        dbDelta($mailboxesSql);

        // Add missing columns to existing table
        self::addMissingColumns();

        // Upgrade single-account installs to the mailbox model
        self::migrateLegacyAwsMailbox();
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
            'is_draft' => "ALTER TABLE $table ADD COLUMN is_draft tinyint(1) DEFAULT 0",
            'workflow_status' => "ALTER TABLE $table ADD COLUMN workflow_status varchar(20) DEFAULT 'open'",
            'assigned_to' => "ALTER TABLE $table ADD COLUMN assigned_to bigint(20) DEFAULT NULL",
            'mailbox_id' => "ALTER TABLE $table ADD COLUMN mailbox_id bigint(20) DEFAULT NULL"
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

        $indexes = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'workflow_status'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table ADD INDEX workflow_status (workflow_status)");
        }

        $indexes = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'assigned_to'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table ADD INDEX assigned_to (assigned_to)");
        }

        $indexes = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'mailbox_id'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table ADD INDEX mailbox_id (mailbox_id)");
        }

        // Composite index for per-mailbox de-duplication lookups
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'message_mailbox'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $table ADD INDEX message_mailbox (message_id(191), mailbox_id)");
        }
    }

    /**
     * Converts a pre-1.1 AWS-only configuration into a default SES
     * mailbox so existing installs keep working after the upgrade.
     */
    private static function migrateLegacyAwsMailbox()
    {
        global $wpdb;

        $table = self::mailboxesTableExists() ? $wpdb->prefix . 'fluent_mailbox_mailboxes' : null;
        if (!$table) {
            return;
        }

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $key = get_option('fluent_mailbox_aws_key');
        $from = get_option('fluent_mailbox_from_email');
        if (!$key || !$from) {
            return;
        }

        // Bare domain identities get the historical "contact@" default
        $email = (strpos($from, '@') === false) ? 'contact@' . $from : $from;

        $mailboxId = \FluentMailbox\Models\Mailbox::create([
            'name' => 'Primary (Amazon SES)',
            'email' => $email,
            'from_name' => get_option('fluent_mailbox_from_name', ''),
            'driver' => 'ses',
            'driver_settings' => [
                'region' => get_option('fluent_mailbox_aws_region', 'us-east-1'),
                'key' => $key,
                'secret' => get_option('fluent_mailbox_aws_secret', ''),
            ],
            'category' => 'business',
            'is_active' => 1,
            'is_default' => 1,
        ]);

        if ($mailboxId) {
            // Existing emails belong to the migrated mailbox
            $emailsTable = $wpdb->prefix . 'fluent_mailbox_emails';
            $wpdb->query($wpdb->prepare(
                "UPDATE $emailsTable SET mailbox_id = %d WHERE mailbox_id IS NULL OR mailbox_id = 0",
                $mailboxId
            ));
        }
    }

    private static function mailboxesTableExists()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fluent_mailbox_mailboxes';
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }
}
