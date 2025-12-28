<?php

namespace FluentMailbox\Models;

class Email
{
    public static function getTable()
    {
        global $wpdb;
        return $wpdb->prefix . 'fluent_mailbox_emails';
    }

    public static function create($data)
    {
        global $wpdb;
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        $format = [
            '%s', // message_id
            '%s', // subject
            '%s', // sender
            '%s', // recipients
            '%s', // body
            '%s', // status
            '%d', // is_read
            '%s', // created_at
            '%s'  // updated_at
        ];

        $wpdb->insert(self::getTable(), $data, $format);
        return $wpdb->insert_id;
    }

    public static function paginate($page = 1, $perPage = 20, $status = 'all')
    {
        global $wpdb;
        $table = self::getTable();
        $offset = ($page - 1) * $perPage;
        
        $where = "WHERE 1=1";
        if ($status !== 'all') {
            $where .= $wpdb->prepare(" AND status = %s", $status);
        } else {
             // If all, we probably don't want trash unless specified
            $where .= " AND status != 'trash'";
        }

        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d OFFSET %d", $perPage, $offset)
        );

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
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function update($id, $data)
    {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        return $wpdb->update(self::getTable(), $data, ['id' => $id]);
    }
}
