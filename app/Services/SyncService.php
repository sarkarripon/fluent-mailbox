<?php

namespace FluentMailbox\Services;

use FluentMailbox\Models\Mailbox;

/**
 * Inbound sync orchestration for polling drivers (IMAP, SES/S3 pull).
 * Runs from WP-Cron and from the manual "refresh" action.
 */
class SyncService
{
    const LOCK_TRANSIENT = 'fluent_mailbox_sync_lock';

    /**
     * Sync a single mailbox through its driver.
     *
     * @return int|\WP_Error Imported count.
     */
    public static function syncMailbox($mailbox)
    {
        $driver = DriverManager::make($mailbox);
        if (is_wp_error($driver)) {
            return $driver;
        }

        $result = $driver->fetchNewEmails($mailbox);

        global $wpdb;
        $table = Mailbox::getTable();
        $wpdb->update($table, ['last_synced_at' => current_time('mysql')], ['id' => (int) $mailbox->id], ['%s'], ['%d']);

        return $result;
    }

    /**
     * Sync all active mailboxes that support polling.
     *
     * @param bool $onlyPolling True for cron (skip push-only drivers).
     * @return array mailbox_id => imported count or WP_Error.
     */
    public static function syncAll($onlyPolling = true)
    {
        $summary = [];

        foreach (Mailbox::all(true) as $mailbox) {
            if ($onlyPolling && self::isPollingMailbox($mailbox) === false) {
                continue;
            }

            $result = self::syncMailbox($mailbox);
            $summary[(int) $mailbox->id] = is_wp_error($result) ? $result : (int) $result;
        }

        return $summary;
    }

    /**
     * Cron handler — guarded against overlapping runs.
     */
    public static function syncFromCron()
    {
        if (get_transient(self::LOCK_TRANSIENT)) {
            return;
        }
        set_transient(self::LOCK_TRANSIENT, 1, 4 * MINUTE_IN_SECONDS);

        try {
            self::syncAll(true);
        } finally {
            delete_transient(self::LOCK_TRANSIENT);
        }
    }

    /**
     * Whether the mailbox's driver polls for inbound mail (null if driver unknown).
     */
    public static function isPollingMailbox($mailbox)
    {
        $meta = DriverManager::driverMeta($mailbox->driver);
        return $meta ? !empty($meta['capabilities']['polling']) : null;
    }

    /**
     * Ensure the 5-minute cron event is scheduled when any polling
     * mailbox exists, and clear it when none do.
     */
    public static function ensureCronSchedule()
    {
        $hasPolling = false;
        foreach (Mailbox::all(true) as $mailbox) {
            if (self::isPollingMailbox($mailbox)) {
                $hasPolling = true;
                break;
            }
        }

        $scheduled = wp_next_scheduled('fluent_mailbox_sync_event');

        if ($hasPolling && !$scheduled) {
            wp_schedule_event(time() + 60, 'fluent_mailbox_five_minutes', 'fluent_mailbox_sync_event');
        } elseif (!$hasPolling && $scheduled) {
            wp_clear_scheduled_hook('fluent_mailbox_sync_event');
        }
    }
}
