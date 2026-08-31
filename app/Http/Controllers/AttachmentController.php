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
     * Permanently delete one protected inbound attachment (file, media
     * row, and its now-empty random directory). Non-protected ids are
     * left alone — compose uploads live in the regular media library.
     */
    public static function deleteProtected($id)
    {
        if (!get_post_meta($id, '_fluent_mailbox_protected', true)) {
            return;
        }
        $file = get_attached_file($id);
        wp_delete_attachment($id, true);
        if ($file) {
            @rmdir(dirname($file)); // only removes the random dir if empty
        }
    }

    /**
     * Delete every protected attachment referenced by an email row's
     * attachments JSON (used on permanent email deletion / empty trash).
     */
    public static function purgeEmailAttachments($attachmentsJson)
    {
        $ids = json_decode((string) $attachmentsJson, true);
        foreach ((array) $ids as $id) {
            if (is_numeric($id)) {
                self::deleteProtected((int) $id);
            }
        }
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

