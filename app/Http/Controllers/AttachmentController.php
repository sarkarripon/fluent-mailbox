<?php

namespace FluentMailbox\Http\Controllers;

class AttachmentController
{
    public function upload($request)
    {
        if (!function_exists('media_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }

        $files = $request->get_file_params();
        
        if (empty($files['file'])) {
            return new \WP_Error('no_file', 'No file uploaded', ['status' => 400]);
        }

        // Handle file upload
        $file = $files['file'];
        
        // Use WordPress media upload
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            return new \WP_Error('upload_error', $upload['error'], ['status' => 400]);
        }

        // Create attachment post
        $attachment_data = [
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachment_id = wp_insert_attachment($attachment_data, $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);

        $attachment = wp_prepare_attachment_for_js($attachment_id);

        return rest_ensure_response([
            'id' => $attachment['id'],
            'url' => $attachment['url'],
            'filename' => $attachment['filename'],
            'filesize' => $attachment['filesizeHumanReadable'],
            'mime' => $attachment['mime']
        ]);
    }

    public function download($request)
    {
        $id = (int) $request->get_param('id');

        $attachment_url = self::urlFor($id);

        if (!$attachment_url) {
            return new \WP_Error('not_found', 'File not found', ['status' => 404]);
        }

        // Return the URL for frontend to handle
        return rest_ensure_response([
            'url' => $attachment_url,
            'id' => $id
        ]);
    }

    /**
     * URL the current admin can fetch the attachment from. Protected
     * inbound files are NOT web-accessible; they get the authenticated
     * admin-ajax streaming URL (cookie + per-attachment nonce) instead.
     */
    public static function urlFor($id)
    {
        if (get_post_meta($id, '_fluent_mailbox_protected', true)) {
            return admin_url('admin-ajax.php?action=fluent_mailbox_attachment&id=' . (int) $id
                . '&_wpnonce=' . wp_create_nonce('fluent_mailbox_attachment_' . (int) $id));
        }
        return wp_get_attachment_url($id);
    }

    /**
     * admin-ajax streamer for protected inbound attachments. Registered
     * on wp_ajax_fluent_mailbox_attachment (logged-in users only); it
     * re-checks the plugin capability and a per-attachment nonce.
     */
    public static function streamProtected()
    {
        $id = (int) ($_GET['id'] ?? 0);

        if (!current_user_can('manage_options')
            || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'fluent_mailbox_attachment_' . $id)) {
            wp_die('Forbidden', '', ['response' => 403]);
        }
        if (!get_post_meta($id, '_fluent_mailbox_protected', true)) {
            wp_die('Not found', '', ['response' => 404]);
        }

        $file = get_attached_file($id);
        if (!$file || !file_exists($file)) {
            wp_die('Not found', '', ['response' => 404]);
        }

        $downloadName = (string) get_post_meta($id, '_fluent_mailbox_filename', true) ?: basename($file);

        nocache_headers();
        header('Content-Type: ' . (get_post_mime_type($id) ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
        header('Content-Length: ' . filesize($file));
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }

    /**
     * Durable pending-purge queue: attachment ids are recorded here
     * BEFORE their email row is deleted, so an interruption (timeout,
     * dying worker, failed file removal) can never strand confidential
     * files with no record of them — the queue is flushed on every
     * delete/empty-trash call and on the sync cron until each purge
     * verifiably completes.
     */
    const PURGE_QUEUE_OPTION = 'fluent_mailbox_pending_attachment_purge';

    /**
     * Permanently delete one protected inbound attachment (file, media
     * row, and its now-empty random directory). Non-protected ids are
     * left alone — compose uploads live in the regular media library.
     *
     * @return bool true when the media row AND file are verifiably gone.
     */
    public static function deleteProtected($id)
    {
        if (!get_post($id)) {
            return true; // already gone
        }
        if (!get_post_meta($id, '_fluent_mailbox_protected', true)) {
            return true; // not ours to delete
        }
        $file = get_attached_file($id);
        wp_delete_attachment($id, true);
        if ($file && file_exists($file)) {
            @unlink($file); // a deletion hook may have vetoed wp_delete_attachment
        }
        $gone = !get_post($id) && (!$file || !file_exists($file));
        if ($gone && $file) {
            @rmdir(dirname($file)); // only removes the random dir if empty
        }
        return $gone;
    }

    /**
     * Record attachment ids as pending purge. Called BEFORE the email
     * row delete; flushPurgeQueue() only ever purges ids no surviving
     * email references, so queued ids for a failed row-delete are
     * simply dropped, never destroyed.
     */
    public static function queuePurge($attachmentsJson)
    {
        $ids = array_values(array_filter(array_map('intval', (array) json_decode((string) $attachmentsJson, true))));
        if (!$ids) {
            return;
        }
        $queue = array_map('intval', (array) get_option(self::PURGE_QUEUE_OPTION, []));
        update_option(self::PURGE_QUEUE_OPTION, array_values(array_unique(array_merge($queue, $ids))), false);
    }

    /**
     * Purge every queued attachment whose email row is gone; ids still
     * referenced by a surviving email are dropped from the queue
     * untouched, ids whose purge fails stay queued for the next flush.
     */
    public static function flushPurgeQueue()
    {
        $queue = array_map('intval', (array) get_option(self::PURGE_QUEUE_OPTION, []));
        if (!$queue) {
            return;
        }

        $remaining = [];
        foreach ($queue as $id) {
            if (self::isReferencedByEmail($id)) {
                continue; // its email survived — keep the attachment, drop the entry
            }
            if (!self::deleteProtected($id)) {
                $remaining[] = $id;
            }
        }
        update_option(self::PURGE_QUEUE_OPTION, array_values($remaining), false);
    }

    private static function isReferencedByEmail($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fluent_mailbox_emails';
        $candidates = $wpdb->get_col($wpdb->prepare(
            "SELECT attachments FROM $table WHERE attachments LIKE %s",
            '%' . $wpdb->esc_like((string) (int) $id) . '%'
        ));
        foreach ($candidates as $json) {
            $ids = json_decode((string) $json, true);
            if (is_array($ids) && in_array((int) $id, array_map('intval', $ids), true)) {
                return true;
            }
        }
        return false;
    }
    
    public function getAttachmentInfo($request)
    {
        $id = (int) $request->get_param('id');
        $attachment = get_post($id);
        
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return new \WP_Error('not_found', 'Attachment not found', ['status' => 404]);
        }
        
        $attachment_data = wp_prepare_attachment_for_js($id);

        return rest_ensure_response([
            'id' => $attachment_data['id'],
            'url' => self::urlFor($id) ?: $attachment_data['url'],
            'filename' => (string) get_post_meta($id, '_fluent_mailbox_filename', true) ?: $attachment_data['filename'],
            'filesize' => $attachment_data['filesizeHumanReadable'],
            'mime' => $attachment_data['mime']
        ]);
    }
}

