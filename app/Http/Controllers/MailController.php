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

        if (empty($to) || empty($subject) || empty($body)) {
            return new \WP_Error('missing_params', 'To, Subject and Body are required', ['status' => 400]);
        }

        $sesService = new SesService();
        $messageId = $sesService->sendEmail($to, $subject, $body);

        if (is_wp_error($messageId)) {
            return $messageId;
        }

        // Store in DB as 'sent'
        $emailId = Email::create([
            'message_id' => $messageId,
            'subject' => $subject,
            'sender' => get_option('fluent_mailbox_from_email', get_bloginfo('admin_email')),
            'recipients' => json_encode(is_array($to) ? $to : [$to]),
            'body' => $body,
            'status' => 'sent',
            'is_read' => 1
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

        return rest_ensure_response($email);
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
}
