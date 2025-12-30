<?php

namespace FluentMailbox\Http\Controllers;

use FluentMailbox\Models\Email;
use FluentMailbox\Services\SesService;

class MailController
{
    public function index($request)
    {
        $page = $request->get_param('page') ?: 1;
        $status = $request->get_param('status') ?: 'all';
        $perPage = 20;

        $response = Email::paginate($page, $perPage, $status);

        return rest_ensure_response($response);
    }

    public function send($request)
    {
        $to = $request->get_param('to');
        $subject = $request->get_param('subject');
        $body = $request->get_param('body'); // Expected HTML
        $cc = $request->get_param('cc');
        $bcc = $request->get_param('bcc');
        $attachments = $request->get_param('attachments'); // Array of attachment IDs
        $draftId = $request->get_param('draft_id'); // If sending from draft

        if (empty($to) || empty($subject) || empty($body)) {
            return new \WP_Error('missing_params', 'To, Subject and Body are required', ['status' => 400]);
        }

        $sesService = new SesService();
        
        // Prepare attachment paths if provided
        $attachmentPaths = [];
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachmentId) {
                $path = get_attached_file($attachmentId);
                if ($path && file_exists($path)) {
                    $attachmentPaths[] = $path;
                }
            }
        }
        
        $messageId = $sesService->sendEmail($to, $subject, $body, $cc, $bcc, $attachmentPaths);

        if (is_wp_error($messageId)) {
            return $messageId;
        }

        // If sending from draft, delete the draft
        if ($draftId) {
            Email::update($draftId, ['status' => 'trash']); // Soft delete draft
        }

        // Store in DB as 'sent'
        // Ensure body is a string, not empty
        $bodyContent = is_string($body) ? $body : (string)$body;
        if (empty($bodyContent)) {
            $bodyContent = ''; // Set to empty string if null/empty
        }
        
        $emailId = Email::create([
            'message_id' => $messageId,
            'subject' => sanitize_text_field($subject),
            'sender' => sanitize_email(get_option('fluent_mailbox_from_email', get_bloginfo('admin_email'))),
            'recipients' => json_encode(is_array($to) ? $to : explode(',', $to)),
            'cc' => $cc ? json_encode(is_array($cc) ? $cc : explode(',', $cc)) : null,
            'bcc' => $bcc ? json_encode(is_array($bcc) ? $bcc : explode(',', $bcc)) : null,
            'body' => wp_kses_post($bodyContent), // Sanitize HTML but preserve formatting
            'attachments' => $attachments ? json_encode($attachments) : null,
            'status' => 'sent',
            'is_read' => 1,
            'is_draft' => 0
        ]);

        return rest_ensure_response([
            'message' => 'Email sent successfully',
            'email_id' => $emailId,
            'aws_message_id' => $messageId
        ]);
    }

    public function get($request)
    {
        $id = $request->get_param('id');
        $email = Email::find($id);
        
        if (!$email) {
             return new \WP_Error('not_found', 'Email not found', ['status' => 404]);
        }

        // Mark as read when viewing
        if (!$email->is_read) {
            Email::update($id, ['is_read' => 1]);
        }

        return rest_ensure_response($email);
    }

    public function update($request)
    {
        $id = $request->get_param('id');
        $email = Email::find($id);
        
        if (!$email) {
            return new \WP_Error('not_found', 'Email not found', ['status' => 404]);
        }

        $data = [];
        $is_read = $request->get_param('is_read');
        if ($is_read !== null) {
            $data['is_read'] = (int) $is_read;
        }

        if (empty($data)) {
            return new \WP_Error('invalid_data', 'No valid fields to update', ['status' => 400]);
        }

        Email::update($id, $data);
        $updated = Email::find($id);

        return rest_ensure_response($updated);
    }

    public function delete($request)
    {
        $id = $request->get_param('id');
        $email = Email::find($id);

        if (!$email) {
            return new \WP_Error('not_found', 'Email not found', ['status' => 404]);
        }

        if ($email->status === 'trash') {
            // Permanent delete? For now just soft delete logic again or ignore
            // In a real app we might delete row
        } else {
             Email::update($id, ['status' => 'trash']);
        }

        return rest_ensure_response(['message' => 'Email moved to trash']);
    }

    public function fetchEmails($request)
    {
        $service = new \FluentMailbox\Services\InboundService();
        $count = $service->fetchNewEmails();

        if (is_wp_error($count)) {
            return $count;
        }

        return rest_ensure_response([
            'success' => true,
            'imported_count' => $count,
            'message' => $count > 0 ? "Imported $count new emails." : "No new emails found in S3."
        ]);
    }

    public function emptyTrash($request)
    {
        $deleted = Email::deleteTrash();
        
        return rest_ensure_response([
            'message' => 'All emails in trash deleted successfully',
            'deleted_count' => $deleted
        ]);
    }

    public function getDrafts($request)
    {
        $page = $request->get_param('page') ?: 1;
        $perPage = 20;
        $response = Email::getDrafts($page, $perPage);
        return rest_ensure_response($response);
    }

    public function saveDraft($request)
    {
        $to = $request->get_param('to');
        $subject = $request->get_param('subject');
        $body = $request->get_param('body');
        $cc = $request->get_param('cc');
        $bcc = $request->get_param('bcc');
        $attachments = $request->get_param('attachments');
        $draftId = $request->get_param('draft_id'); // For updating existing draft

        // Ensure body is a string
        $bodyContent = is_string($body) ? $body : (string)($body ?: '');
        
        $data = [
            'subject' => $subject ?: '(No Subject)',
            'sender' => get_option('fluent_mailbox_from_email', get_bloginfo('admin_email')),
            'recipients' => $to ? json_encode(is_array($to) ? $to : explode(',', $to)) : json_encode([]),
            'cc' => $cc ? json_encode(is_array($cc) ? $cc : explode(',', $cc)) : null,
            'bcc' => $bcc ? json_encode(is_array($bcc) ? $bcc : explode(',', $bcc)) : null,
            'body' => wp_kses_post($bodyContent), // Sanitize HTML but preserve formatting
            'attachments' => $attachments ? json_encode($attachments) : null,
            'status' => 'draft',
            'is_draft' => 1,
            'is_read' => 1
        ];

        if ($draftId) {
            // Update existing draft
            Email::update($draftId, $data);
            $draft = Email::find($draftId);
        } else {
            // Create new draft
            $draftId = Email::create($data);
            $draft = Email::find($draftId);
        }

        return rest_ensure_response([
            'message' => 'Draft saved',
            'draft_id' => $draftId,
            'draft' => $draft
        ]);
    }

    public function deleteDraft($request)
    {
        $id = $request->get_param('id');
        $email = Email::find($id);

        if (!$email || !$email->is_draft) {
            return new \WP_Error('not_found', 'Draft not found', ['status' => 404]);
        }

        Email::update($id, ['status' => 'trash']);

        return rest_ensure_response(['message' => 'Draft deleted']);
    }
}
