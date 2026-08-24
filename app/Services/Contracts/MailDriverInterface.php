<?php

namespace FluentMailbox\Services\Contracts;

/**
 * Contract every mail driver must implement.
 *
 * Drivers are stateless: connection settings are passed into each
 * method so credentials can be tested before a mailbox is saved.
 */
interface MailDriverInterface
{
    /**
     * Verify credentials/connectivity using the given settings.
     *
     * @param array $settings Driver settings key => value.
     * @return true|\WP_Error
     */
    public function testConnection(array $settings = []);

    /**
     * Send an email.
     *
     * Supported $args keys:
     *   to (string|array), subject (string), body (html string),
     *   cc, bcc (string|array), attachments (array of file paths),
     *   from_email, from_name, reply_to
     *
     * @return string|\WP_Error Provider message id on success.
     */
    public function send(array $args, array $settings = []);

    /**
     * Pull new inbound emails for a mailbox (polling drivers).
     *
     * @param object $mailbox Mailbox row incl. driver_settings and sync_state.
     * @return int|\WP_Error Number of newly imported emails.
     */
    public function fetchNewEmails($mailbox);

    /**
     * Handle an inbound webhook POST addressed to a mailbox (push drivers).
     * The router has already verified the URL's per-mailbox inbound secret
     * before this is called; drivers add their own provider verification.
     *
     * @param \WP_REST_Request $request
     * @param object $mailbox Mailbox row the webhook URL addresses.
     * @return \WP_Error|true|string WP_Error on failure, true if handled, 'duplicate' if skipped.
     */
    public function handleWebhook($request, $mailbox);
}
