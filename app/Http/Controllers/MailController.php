<?php

namespace FluentMailbox\Http\Controllers;

use FluentMailbox\Models\Email;
use FluentMailbox\Services\SesService;

class MailController
{
    public function getUsers($request)
    {
        $users = get_users([
            'fields' => ['ID', 'display_name', 'user_email']
        ]);

        $payload = array_map(function ($user) {
            return [
                'id' => (int)$user->ID,
                'display_name' => (string)$user->display_name,
                'user_email' => (string)$user->user_email
            ];
        }, $users);

        return rest_ensure_response($payload);
    }

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

    public function getWorkflow($request)
    {
        $id = (int)$request->get_param('id');
        $email = Email::find($id);

        if (!$email) {
            return new \WP_Error('not_found', 'Email not found', ['status' => 404]);
        }

        return rest_ensure_response([
            'workflow_status' => isset($email->workflow_status) ? (string)$email->workflow_status : 'open',
            'assigned_to' => isset($email->assigned_to) ? (int)$email->assigned_to : null
        ]);
    }

    public function updateWorkflow($request)
    {
        $id = (int)$request->get_param('id');
        $email = Email::find($id);

        if (!$email) {
            return new \WP_Error('not_found', 'Email not found', ['status' => 404]);
        }

        $data = [];

        $workflowStatus = $request->get_param('workflow_status');
        if ($workflowStatus !== null) {
            $workflowStatus = sanitize_key($workflowStatus);
            $allowed = ['open', 'pending', 'resolved'];
            if (!in_array($workflowStatus, $allowed, true)) {
                return new \WP_Error('invalid_workflow_status', 'Invalid workflow_status', ['status' => 400]);
            }
            $data['workflow_status'] = $workflowStatus;
        }

        $assignedTo = $request->get_param('assigned_to');
        if ($assignedTo !== null) {
            if ($assignedTo === '' || $assignedTo === false) {
                $data['assigned_to'] = null;
            } else {
                $assignedTo = (int)$assignedTo;
                if ($assignedTo > 0 && !get_user_by('id', $assignedTo)) {
                    return new \WP_Error('invalid_assigned_to', 'Assigned user not found', ['status' => 400]);
                }
                $data['assigned_to'] = $assignedTo > 0 ? $assignedTo : null;
            }
        }

        if (empty($data)) {
            return new \WP_Error('invalid_data', 'No valid fields to update', ['status' => 400]);
        }

        Email::update($id, $data);
        return $this->getWorkflow($request);
    }

    public function getNotes($request)
    {
        $emailId = (int)$request->get_param('id');
        $email = Email::find($emailId);

        if (!$email) {
            return new \WP_Error('not_found', 'Email not found', ['status' => 404]);
        }

        $notes = Email::getNotes($emailId);
        $payload = array_map(function ($note) {
            $user = get_user_by('id', (int)$note->user_id);
            return [
                'id' => (int)$note->id,
                'email_id' => (int)$note->email_id,
                'user_id' => (int)$note->user_id,
                'user_name' => $user ? (string)$user->display_name : 'Unknown',
                'note' => (string)$note->note,
                'created_at' => (string)$note->created_at
            ];
        }, $notes);

        return rest_ensure_response($payload);
    }

    public function addNote($request)
    {
        $emailId = (int)$request->get_param('id');
        $email = Email::find($emailId);

        if (!$email) {
            return new \WP_Error('not_found', 'Email not found', ['status' => 404]);
        }

        $note = $request->get_param('note');
        $note = is_string($note) ? trim($note) : '';
        if ($note === '') {
            return new \WP_Error('missing_note', 'Note is required', ['status' => 400]);
        }

        $noteId = Email::addNote($emailId, get_current_user_id(), sanitize_textarea_field($note));
        $notes = Email::getNotes($emailId);

        // Return the newest note (we order DESC)
        $newNote = !empty($notes) ? $notes[0] : null;
        if (!$newNote) {
            return rest_ensure_response(['id' => (int)$noteId]);
        }

        $user = get_user_by('id', (int)$newNote->user_id);

        return rest_ensure_response([
            'id' => (int)$newNote->id,
            'email_id' => (int)$newNote->email_id,
            'user_id' => (int)$newNote->user_id,
            'user_name' => $user ? (string)$user->display_name : 'Unknown',
            'note' => (string)$newNote->note,
            'created_at' => (string)$newNote->created_at
        ]);
    }

    public function deleteNote($request)
    {
        $noteId = (int)$request->get_param('id');
        if ($noteId <= 0) {
            return new \WP_Error('invalid_note', 'Invalid note id', ['status' => 400]);
        }

        Email::deleteNote($noteId);
        return rest_ensure_response(['message' => 'Note deleted']);
    }

    public function delete($request)
    {
        $id = $request->get_param('id');
        $email = Email::find($id);

        if (!$email) {
            return new \WP_Error('not_found', 'Email not found', ['status' => 404]);
        }

        // Check for permanent delete parameter from query string or body
        $permanent = $request->get_param('permanent');
        if ($permanent === null) {
            // Try getting from query params
            $permanent = isset($_GET['permanent']) ? filter_var($_GET['permanent'], FILTER_VALIDATE_BOOLEAN) : false;
        } else {
            $permanent = filter_var($permanent, FILTER_VALIDATE_BOOLEAN);
        }

        // If email is already in trash OR permanent flag is set, permanently delete
        if ($email->status === 'trash' || $permanent) {
            // Permanent delete - remove from database
            global $wpdb;
            $table = Email::getTable();
            $wpdb->delete($table, ['id' => $id], ['%d']);
            return rest_ensure_response(['message' => 'Email permanently deleted']);
        } else {
            // Soft delete - move to trash
            Email::update($id, ['status' => 'trash']);
            return rest_ensure_response(['message' => 'Email moved to trash']);
        }
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
