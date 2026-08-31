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
            message_id varchar(255) DEFAULT NULL,
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

        // Copy any remaining legacy AWS options into SES mailbox rows —
        // after this, runtime code never reads the legacy options
        self::backfillSesSettings();

        // Every mailbox needs an inbound secret for the public webhook endpoint
        self::backfillInboundSecrets();

        // MUST run after migrateLegacyAwsMailbox(): the legacy upgrade
        // assigns mailbox_id to every pre-mailbox row, and that bulk
        // assignment has to happen before the unique dedup constraint
        // exists — otherwise legacy duplicate rows would make it fail
        self::ensureDedupIndex();

        // Data-migration failures (not just schema shape) must also keep
        // the version from advancing, so the migration retries next load
        return self::verifySchema() && empty(self::$migrationErrors);
    }

    /** @var string[] Data-migration failures collected during migrate(). */
    private static $migrationErrors = [];

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
            'mailbox_id' => "ALTER TABLE $table ADD COLUMN mailbox_id bigint(20) DEFAULT NULL",
            'dedup_hash' => "ALTER TABLE $table ADD COLUMN dedup_hash char(64) DEFAULT NULL"
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

    }

    /**
     * DB-enforced per-mailbox de-duplication on a FULL-VALUE key: the
     * unique index pairs SHA-256(message_id) with mailbox_id, so two
     * distinct ids can never be conflated the way a length-limited
     * index prefix could. NON-DESTRUCTIVE: no row is ever deleted —
     * where existing rows would collide, the newer rows simply keep a
     * NULL hash (NULL tuples never participate in a unique constraint),
     * so uniqueness applies to future inserts while all existing data,
     * notes, and tags stay untouched.
     *
     * Runs after migrateLegacyAwsMailbox() so legacy rows already carry
     * their mailbox_id before the constraint exists.
     */
    private static function ensureDedupIndex()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fluent_mailbox_emails';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return;
        }

        $unique = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'uniq_message_mailbox'");
        $onHash = false;
        foreach ($unique as $indexRow) {
            if ($indexRow->Column_name === 'dedup_hash') {
                $onHash = true;
            }
        }
        if (!empty($unique) && !$onHash) {
            // Interim 1.1.3-dev index on a message_id prefix — replace it
            $wpdb->query("ALTER TABLE $table DROP INDEX uniq_message_mailbox");
            $unique = [];
        }
        // Dedup is INBOUND-ONLY: a sent row sharing its Message-ID with
        // the delivered copy (self-addressed mail) must never suppress
        // the inbox import. Clear any hash earlier versions put on
        // sent/draft rows; idempotent, so it runs on every migrate.
        $wpdb->query("UPDATE $table SET dedup_hash = NULL WHERE dedup_hash IS NOT NULL AND (status = 'sent' OR status = 'draft' OR is_draft = 1)");

        if (empty($unique)) {
            $wpdb->query("ALTER TABLE $table MODIFY message_id varchar(255) NULL DEFAULT NULL");
            $wpdb->query("UPDATE $table SET message_id = NULL WHERE message_id = ''");
            $wpdb->query("UPDATE $table SET dedup_hash = SHA2(message_id, 256)
                WHERE message_id IS NOT NULL AND dedup_hash IS NULL
                AND (status IS NULL OR (status <> 'sent' AND status <> 'draft'))
                AND (is_draft IS NULL OR is_draft = 0)");
            // Neutralize (not delete) collisions: the oldest row keeps its
            // hash, later ones get NULL so the unique index can be created.
            // NULL-safe mailbox comparison (<=>) so still-unassigned rows
            // are neutralized too and can never trip the constraint later
            $wpdb->query("UPDATE $table e2 JOIN $table e1
                ON e1.dedup_hash = e2.dedup_hash
                AND e1.mailbox_id <=> e2.mailbox_id
                AND e2.id > e1.id
                SET e2.dedup_hash = NULL");
            $legacy = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'message_mailbox'");
            if (!empty($legacy)) {
                $wpdb->query("ALTER TABLE $table DROP INDEX message_mailbox");
            }
            $wpdb->query("ALTER TABLE $table ADD UNIQUE INDEX uniq_message_mailbox (dedup_hash, mailbox_id)");
        }
    }

    /**
     * Post-migration schema verification. The caller must not record the
     * new database version unless this passes — otherwise a failed DDL
     * would be silently skipped forever.
     */
    public static function verifySchema()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fluent_mailbox_emails';

        $columns = $wpdb->get_col("DESCRIBE $table");
        if (!in_array('dedup_hash', $columns, true)) {
            return false;
        }
        foreach ($wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'uniq_message_mailbox'") as $indexRow) {
            if ($indexRow->Column_name === 'dedup_hash' && (int) $indexRow->Non_unique === 0) {
                return true;
            }
        }
        return false;
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
            // Existing emails belong to the migrated mailbox. Runs before
            // ensureDedupIndex(), so the bulk assignment cannot collide
            // with the unique dedup constraint.
            $emailsTable = $wpdb->prefix . 'fluent_mailbox_emails';
            $assigned = $wpdb->query($wpdb->prepare(
                "UPDATE $emailsTable SET mailbox_id = %d WHERE mailbox_id IS NULL OR mailbox_id = 0",
                $mailboxId
            ));
            if ($assigned === false) {
                self::$migrationErrors[] = 'Legacy mailbox assignment failed: ' . $wpdb->last_error;
                \FluentMailbox\Services\Logger::log('Legacy mailbox assignment failed', ['error' => $wpdb->last_error]);
            }
        }
    }

    /**
     * The legacy fluent_mailbox_aws_* / s3_bucket / sns_topic options are
     * read ONLY here: any SES mailbox still missing credentials or the
     * inbound bucket gets them copied into driver_settings. Covers 1.1.0
     * installs migrated before the bucket/topic moved into the mailbox
     * row (the runtime option fallbacks are gone). Idempotent.
     */
    private static function backfillSesSettings()
    {
        global $wpdb;

        if (!self::mailboxesTableExists()) {
            return;
        }

        $legacy = [
            'key' => get_option('fluent_mailbox_aws_key', ''),
            'secret' => get_option('fluent_mailbox_aws_secret', ''),
            'region' => get_option('fluent_mailbox_aws_region', 'us-east-1'),
            'inbound_bucket' => get_option('fluent_mailbox_s3_bucket', ''),
            'sns_topic_arn' => get_option('fluent_mailbox_sns_topic_arn', ''),
        ];
        if (!$legacy['key'] && !$legacy['inbound_bucket']) {
            return;
        }

        $table = $wpdb->prefix . 'fluent_mailbox_mailboxes';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id, driver_settings FROM $table WHERE driver = %s", 'ses'));
        foreach ($rows as $row) {
            $settings = json_decode((string) $row->driver_settings, true);
            if (!is_array($settings)) {
                $settings = [];
            }

            $changed = false;
            if (empty($settings['key']) && $legacy['key']) {
                $settings['key'] = $legacy['key'];
                $settings['secret'] = $legacy['secret'];
                $settings['region'] = $legacy['region'];
                $changed = true;
            }
            if (empty($settings['inbound_bucket']) && $legacy['inbound_bucket']) {
                $settings['inbound_bucket'] = $legacy['inbound_bucket'];
                if ($legacy['sns_topic_arn']) {
                    $settings['sns_topic_arn'] = $legacy['sns_topic_arn'];
                }
                $changed = true;
            }

            if ($changed) {
                $wpdb->update($table, ['driver_settings' => wp_json_encode($settings)], ['id' => (int) $row->id], ['%s'], ['%d']);
            }
        }
    }

    /**
     * Adds an inbound_secret to any mailbox created before the unified
     * webhook endpoint existed. Idempotent.
     */
    private static function backfillInboundSecrets()
    {
        global $wpdb;

        if (!self::mailboxesTableExists()) {
            return;
        }

        $table = $wpdb->prefix . 'fluent_mailbox_mailboxes';
        $rows = $wpdb->get_results("SELECT id, driver_settings FROM $table");
        foreach ($rows as $row) {
            $settings = json_decode((string) $row->driver_settings, true);
            if (!is_array($settings)) {
                $settings = [];
            }
            if (!empty($settings['inbound_secret'])) {
                continue;
            }
            $settings['inbound_secret'] = wp_generate_password(32, false);
            $wpdb->update($table, ['driver_settings' => wp_json_encode($settings)], ['id' => (int) $row->id], ['%s'], ['%d']);
        }
    }

    private static function mailboxesTableExists()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fluent_mailbox_mailboxes';
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }
}
