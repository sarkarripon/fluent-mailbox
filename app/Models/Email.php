<?php

namespace FluentMailbox\Models;

use FluentMailbox\Common\DatabaseMigration;

class Email
{
    private static function notesTableExists()
    {
        global $wpdb;
        $table = self::getNotesTable();
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    private static function ensureNotesTable()
    {
        if (self::notesTableExists()) {
            return true;
        }

        // Attempt to create missing table for existing installs where migrations didn't run.
        DatabaseMigration::migrate();

        return self::notesTableExists();
    }

    public static function getTable()
    {
        global $wpdb;
        return $wpdb->prefix . 'fluent_mailbox_emails';
    }

    public static function getNotesTable()
    {
        global $wpdb;
        return $wpdb->prefix . 'fluent_mailbox_email_notes';
    }

    public static function create($data)
    {
        global $wpdb;
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        // Empty message ids must be NULL: the uniq_message_mailbox index
        // treats NULL tuples as never-colliding, '' as a real value
        if (array_key_exists('message_id', $data) && $data['message_id'] === '') {
            $data['message_id'] = null;
        }

        // Full-value dedup key: hashed from the complete Message-ID, so
        // the unique index never conflates distinct ids the way a
        // length-limited column prefix could
        if (!empty($data['message_id'])) {
            $data['dedup_hash'] = hash('sha256', (string) $data['message_id']);
        }

        // Build format array dynamically based on data keys
        $format = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['is_read', 'is_draft', 'assigned_to', 'mailbox_id'])) {
                $format[] = '%d'; // Integer
            } else {
                $format[] = '%s'; // String
            }
        }

        if ($wpdb->insert(self::getTable(), $data, $format) === false) {
            return false;
        }
        return $wpdb->insert_id;
    }

    public static function paginate($page = 1, $perPage = 20, $status = 'all', $mailboxId = null)
    {
        global $wpdb;
        $table = self::getTable();
        $offset = ($page - 1) * $perPage;

        $where = "WHERE 1=1";
        if ($status !== 'all') {
            if ($status === 'inbox') {
                // Include inbox status, NULL, or empty string (defaults to inbox)
                $where .= " AND (status = 'inbox' OR status IS NULL OR status = '')";
            } else {
                $where .= $wpdb->prepare(" AND status = %s", $status);
            }
        } else {
             // If all, we probably don't want trash unless specified
            $where .= " AND status != 'trash'";
        }

        if ($mailboxId) {
            $where .= $wpdb->prepare(" AND mailbox_id = %d", (int) $mailboxId);
        }

        // Build query - need to handle the WHERE clause separately from prepare
        $query = "SELECT * FROM `$table` $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $items = $wpdb->get_results(
            $wpdb->prepare($query, $perPage, $offset)
        );

        foreach ($items as $item) {
            $item->is_read = (int) $item->is_read;
            $item->is_draft = (int) $item->is_draft;
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");

        return [
            'data' => $items,
            'total' => (int)$total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    public static function find($id)
    {
        global $wpdb;
        $table = self::getTable();
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($item) {
            $item->is_read = (int) $item->is_read;
            $item->is_draft = (int) $item->is_draft;
        }
        return $item;
    }

    public static function update($id, $data)
    {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');

        $format = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['is_read', 'is_draft', 'assigned_to', 'mailbox_id'])) {
                $format[] = '%d';
            } else {
                $format[] = '%s';
            }
        }

        return $wpdb->update(self::getTable(), $data, ['id' => $id], $format, ['%d']);
    }

    public static function getNotes($emailId)
    {
        global $wpdb;
        $table = self::getNotesTable();

        if (!self::ensureNotesTable()) {
            return [];
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE email_id = %d ORDER BY created_at DESC",
                $emailId
            )
        );
    }

    public static function addNote($emailId, $userId, $note)
    {
        global $wpdb;
        $table = self::getNotesTable();

        if (!self::ensureNotesTable()) {
            return 0;
        }

        $wpdb->insert(
            $table,
            [
                'email_id' => (int)$emailId,
                'user_id' => (int)$userId,
                'note' => (string)$note,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s']
        );

        return $wpdb->insert_id;
    }

    public static function deleteNote($noteId)
    {
        global $wpdb;
        $table = self::getNotesTable();
        return $wpdb->delete($table, ['id' => (int)$noteId], ['%d']);
    }


    public static function getDrafts($page = 1, $perPage = 20, $mailboxId = null)
    {
        global $wpdb;
        $table = self::getTable();
        $offset = ($page - 1) * $perPage;

        $where = "WHERE is_draft = 1 AND status = 'draft'";
        if ($mailboxId) {
            $where .= $wpdb->prepare(" AND mailbox_id = %d", (int) $mailboxId);
        }

        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table $where ORDER BY updated_at DESC LIMIT %d OFFSET %d", $perPage, $offset)
        );

        foreach ($items as $item) {
            $item->is_read = (int) $item->is_read;
            $item->is_draft = (int) $item->is_draft;
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");

        return [
            'data' => $items,
            'total' => (int)$total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
}
