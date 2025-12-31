<?php

namespace FluentMailbox\Models;

class Tag
{
    protected $wpdb;
    protected $table;
    protected $emailTagsTable;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'fluent_mailbox_tags';
        $this->emailTagsTable = $wpdb->prefix . 'fluent_mailbox_email_tags';
    }

    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get all tags
     */
    public function all()
    {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->table} ORDER BY name ASC",
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Create a new tag
     */
    public function create($data)
    {
        $this->wpdb->insert(
            $this->table,
            [
                'name' => sanitize_text_field($data['name']),
                'color' => sanitize_text_field($data['color'] ?? '#3B82F6'),
                'created_at' => current_time('mysql')
            ]
        );

        return $this->wpdb->insert_id;
    }

    /**
     * Update a tag
     */
    public function update($id, $data)
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = sanitize_text_field($data['name']);
        }

        if (isset($data['color'])) {
            $updateData['color'] = sanitize_text_field($data['color']);
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id]
        );
    }

    /**
     * Delete a tag
     */
    public function delete($id)
    {
        // First delete all email-tag associations
        $this->wpdb->delete(
            $this->emailTagsTable,
            ['tag_id' => $id]
        );

        // Then delete the tag
        return $this->wpdb->delete(
            $this->table,
            ['id' => $id]
        );
    }

    /**
     * Get tags for a specific email
     */
    public function getEmailTags($emailId)
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT t.* FROM {$this->table} t
                INNER JOIN {$this->emailTagsTable} et ON t.id = et.tag_id
                WHERE et.email_id = %d
                ORDER BY t.name ASC",
                $emailId
            ),
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Add tag to email
     */
    public function addToEmail($emailId, $tagId)
    {
        // Check if association already exists
        $exists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->emailTagsTable}
                WHERE email_id = %d AND tag_id = %d",
                $emailId,
                $tagId
            )
        );

        if ($exists) {
            return true; // Already exists
        }

        $this->wpdb->insert(
            $this->emailTagsTable,
            [
                'email_id' => $emailId,
                'tag_id' => $tagId,
                'created_at' => current_time('mysql')
            ]
        );

        return $this->wpdb->insert_id;
    }

    /**
     * Remove tag from email
     */
    public function removeFromEmail($emailId, $tagId)
    {
        return $this->wpdb->delete(
            $this->emailTagsTable,
            [
                'email_id' => $emailId,
                'tag_id' => $tagId
            ]
        );
    }

    /**
     * Get all emails with a specific tag
     */
    public function getEmailsByTag($tagId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $emailsTable = $this->wpdb->prefix . 'fluent_mailbox_emails';

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT e.* FROM {$emailsTable} e
                INNER JOIN {$this->emailTagsTable} et ON e.id = et.email_id
                WHERE et.tag_id = %d
                ORDER BY e.created_at DESC
                LIMIT %d OFFSET %d",
                $tagId,
                $perPage,
                $offset
            ),
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Check if tag name already exists
     */
    public function existsByName($name, $excludeId = null)
    {
        $query = $this->wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE name = %s",
            $name
        );

        if ($excludeId) {
            $query .= $this->wpdb->prepare(" AND id != %d", $excludeId);
        }

        return (bool) $this->wpdb->get_var($query);
    }
}
