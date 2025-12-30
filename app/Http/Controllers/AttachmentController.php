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
        $file_path = get_attached_file($id);
        
        if (!$file_path || !file_exists($file_path)) {
            return new \WP_Error('not_found', 'File not found', ['status' => 404]);
        }

        $mime_type = get_post_mime_type($id);
        $filename = basename($file_path);

        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

