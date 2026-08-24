<?php

namespace FluentMailbox\Models;

class Mailbox
{
    const CATEGORIES = ['marketing', 'business', 'support', 'other'];

    public static function getTable()
    {
        global $wpdb;
        return $wpdb->prefix . 'fluent_mailbox_mailboxes';
    }

    /**
     * Decoded driver_settings for a mailbox row.
     */
    public static function settingsOf($mailbox)
    {
        if (!is_object($mailbox) || empty($mailbox->driver_settings)) {
            return [];
        }
        $settings = json_decode($mailbox->driver_settings, true);
        return is_array($settings) ? $settings : [];
    }

    /**
     * Decoded sync_state for a mailbox row.
     */
    public static function syncStateOf($mailbox)
    {
        if (!is_object($mailbox) || empty($mailbox->sync_state)) {
            return [];
        }
        $state = json_decode($mailbox->sync_state, true);
        return is_array($state) ? $state : [];
    }

    public static function all($onlyActive = false)
    {
        global $wpdb;
        $table = self::getTable();
        $where = $onlyActive ? 'WHERE is_active = 1' : '';
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY id ASC");
    }

    public static function findAllByDriver($driver)
    {
        global $wpdb;
        $table = self::getTable();
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE driver = %s ORDER BY id ASC", $driver));
    }

    public static function find($id)
    {
        global $wpdb;
        $table = self::getTable();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", (int) $id));
    }

    public static function findByEmail($email)
    {
        global $wpdb;
        $table = self::getTable();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE is_active = 1 AND LOWER(email) = %s LIMIT 1", strtolower(trim($email))));
    }

    public static function getDefault()
    {
        global $wpdb;
        $table = self::getTable();
        $mailbox = $wpdb->get_row("SELECT * FROM $table WHERE is_active = 1 AND is_default = 1 ORDER BY id ASC LIMIT 1");
        if (!$mailbox) {
            $mailbox = $wpdb->get_row("SELECT * FROM $table WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        }
        return $mailbox;
    }

    public static function hasActive()
    {
        global $wpdb;
        $table = self::getTable();
        // Guard against pre-migration states where the table doesn't exist yet
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        if (!$exists) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_active = 1") > 0;
    }

    public static function create($data)
    {
        global $wpdb;

        $data = self::normalize($data);
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        if (!empty($data['is_default'])) {
            self::clearDefaults();
        }

        $format = [];
        foreach ($data as $key => $value) {
            $format[] = in_array($key, ['is_active', 'is_default'], true) ? '%d' : '%s';
        }

        $wpdb->insert(self::getTable(), $data, $format);
        $id = $wpdb->insert_id;

        if ($id && empty($data['is_default'])) {
            // First mailbox always becomes the default
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::getTable());
            if ($count === 1) {
                $wpdb->update(self::getTable(), ['is_default' => 1], ['id' => $id], ['%d'], ['%d']);
            }
        }

        return $id;
    }

    public static function update($id, $data)
    {
        global $wpdb;

        $data = self::normalize($data);
        if (isset($data['driver_settings']) && is_array($data['driver_settings'])) {
            $data['driver_settings'] = json_encode($data['driver_settings']);
        }
        $data['updated_at'] = current_time('mysql');

        if (!empty($data['is_default'])) {
            self::clearDefaults();
        }

        $format = [];
        foreach ($data as $key => $value) {
            $format[] = in_array($key, ['is_active', 'is_default'], true) ? '%d' : '%s';
        }

        return $wpdb->update(self::getTable(), $data, ['id' => (int) $id], $format, ['%d']);
    }

    public static function delete($id)
    {
        global $wpdb;
        $table = self::getTable();
        $wasDefault = $wpdb->get_var($wpdb->prepare("SELECT is_default FROM $table WHERE id = %d", (int) $id));
        $deleted = $wpdb->delete($table, ['id' => (int) $id], ['%d']);

        if ($deleted && $wasDefault) {
            $next = $wpdb->get_row("SELECT id FROM $table WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
            if ($next) {
                $wpdb->update($table, ['is_default' => 1], ['id' => $next->id], ['%d'], ['%d']);
            }
        }

        return $deleted;
    }

    public static function updateSyncState($id, $state)
    {
        global $wpdb;
        $table = self::getTable();
        $wpdb->update(
            $table,
            ['sync_state' => wp_json_encode($state), 'last_synced_at' => current_time('mysql')],
            ['id' => (int) $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    /**
     * Pick the mailbox an inbound email belongs to by matching its
     * recipient addresses against configured mailboxes. Falls back
     * to the default mailbox, then null.
     */
    public static function routeInbound(array $recipients)
    {
        foreach ($recipients as $recipient) {
            if (!is_string($recipient) || strpos($recipient, '@') === false) {
                continue;
            }
            // Strip "Name <email>" formatting if present
            if (preg_match('/<([^>]+)>/', $recipient, $m)) {
                $recipient = $m[1];
            }
            $mailbox = self::findByEmail($recipient);
            if ($mailbox) {
                return (int) $mailbox->id;
            }
        }

        $default = self::getDefault();
        return $default ? (int) $default->id : null;
    }

    /**
     * Unread inbox counts, keyed by mailbox_id. Key 0 = unassigned.
     */
    public static function unreadCounts()
    {
        global $wpdb;
        $emailsTable = Email::getTable();
        return $wpdb->get_results(
            "SELECT IFNULL(mailbox_id, 0) AS mailbox_id, COUNT(*) AS unread
             FROM $emailsTable
             WHERE (status = 'inbox' OR status IS NULL OR status = '') AND is_read = 0
             GROUP BY mailbox_id",
            OBJECT_K
        );
    }

    private static function clearDefaults()
    {
        global $wpdb;
        $table = self::getTable();
        $wpdb->query("UPDATE $table SET is_default = 0 WHERE is_default = 1");
    }

    private static function normalize($data)
    {
        if (isset($data['driver_settings']) && is_array($data['driver_settings'])) {
            $data['driver_settings'] = wp_json_encode($data['driver_settings']);
        }

        if (isset($data['category']) && !in_array($data['category'], self::CATEGORIES, true)) {
            $data['category'] = 'other';
        }

        foreach (['is_active', 'is_default'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $data[$flag] = empty($data[$flag]) ? 0 : 1;
            }
        }

        return $data;
    }
}
