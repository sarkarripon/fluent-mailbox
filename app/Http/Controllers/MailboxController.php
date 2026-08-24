<?php

namespace FluentMailbox\Http\Controllers;

use FluentMailbox\Models\Email;
use FluentMailbox\Models\Mailbox;
use FluentMailbox\Services\DriverManager;
use FluentMailbox\Services\SyncService;

/**
 * CRUD + actions for connected mailboxes. All routes are admin-only
 * (manage_options); driver secrets are masked on read and masked
 * values submitted back mean "leave unchanged".
 */
class MailboxController
{
    public function index($request)
    {
        $counts = Mailbox::unreadCounts();

        $payload = [];
        foreach (Mailbox::all() as $mailbox) {
            $payload[] = $this->present($mailbox, $counts);
        }

        return rest_ensure_response([
            'mailboxes' => $payload,
            'unassigned_unread' => isset($counts[0]) ? (int) $counts[0]->unread : 0,
        ]);
    }

    public function show($request)
    {
        $mailbox = Mailbox::find($request->get_param('id'));
        if (!$mailbox) {
            return new \WP_Error('not_found', 'Mailbox not found', ['status' => 404]);
        }

        return rest_ensure_response($this->present($mailbox, Mailbox::unreadCounts()));
    }

    public function store($request)
    {
        $params = $request->get_params();

        $error = $this->validate($params, null);
        if (is_wp_error($error)) {
            return $error;
        }

        $settings = is_array($params['driver_settings'] ?? null) ? $params['driver_settings'] : [];
        unset($settings['inbound_secret']); // Always server-generated

        $id = Mailbox::create([
            'name' => sanitize_text_field($params['name']),
            'email' => sanitize_email($params['email']),
            'from_name' => sanitize_text_field($params['from_name'] ?? ''),
            'driver' => $params['driver'],
            'driver_settings' => $settings,
            'category' => $params['category'] ?? 'other',
            'color' => sanitize_text_field($params['color'] ?? ''),
            'is_active' => array_key_exists('is_active', $params) ? (int) !empty($params['is_active']) : 1,
            'is_default' => (int) !empty($params['is_default']),
        ]);

        if (!$id) {
            return new \WP_Error('db_error', 'Could not create mailbox', ['status' => 500]);
        }

        SyncService::ensureCronSchedule();

        return rest_ensure_response([
            'message' => 'Mailbox created',
            'mailbox' => $this->present(Mailbox::find($id)),
        ]);
    }

    public function update($request)
    {
        $mailbox = Mailbox::find($request->get_param('id'));
        if (!$mailbox) {
            return new \WP_Error('not_found', 'Mailbox not found', ['status' => 404]);
        }

        $params = $request->get_params();

        $error = $this->validate($params, $mailbox);
        if (is_wp_error($error)) {
            return $error;
        }

        $data = [];
        if (array_key_exists('name', $params)) {
            $data['name'] = sanitize_text_field($params['name']);
        }
        if (array_key_exists('email', $params)) {
            $data['email'] = sanitize_email($params['email']);
        }
        if (array_key_exists('from_name', $params)) {
            $data['from_name'] = sanitize_text_field($params['from_name']);
        }
        if (array_key_exists('driver', $params)) {
            $data['driver'] = $params['driver'];
        }
        if (array_key_exists('category', $params)) {
            $data['category'] = $params['category'];
        }
        if (array_key_exists('color', $params)) {
            $data['color'] = sanitize_text_field($params['color']);
        }
        foreach (['is_active', 'is_default'] as $flag) {
            if (array_key_exists($flag, $params)) {
                $data[$flag] = (int) !empty($params[$flag]);
            }
        }

        if (is_array($params['driver_settings'] ?? null)) {
            $stored = Mailbox::settingsOf($mailbox);
            $merged = $stored;
            foreach ($params['driver_settings'] as $key => $value) {
                if ($key === 'inbound_secret') {
                    continue; // Server-generated, never client-writable
                }
                // A masked value round-tripped from the UI means "unchanged"
                if (is_string($value) && $this->isMasked($value) && isset($stored[$key])) {
                    continue;
                }
                $merged[$key] = $value;
            }
            $data['driver_settings'] = $merged;
        }

        if ($data) {
            Mailbox::update($mailbox->id, $data);
        }

        SyncService::ensureCronSchedule();

        return rest_ensure_response([
            'message' => 'Mailbox updated',
            'mailbox' => $this->present(Mailbox::find($mailbox->id)),
        ]);
    }

    public function destroy($request)
    {
        global $wpdb;

        $mailbox = Mailbox::find($request->get_param('id'));
        if (!$mailbox) {
            return new \WP_Error('not_found', 'Mailbox not found', ['status' => 404]);
        }

        // Refuse to remove the last active mailbox — the plugin would
        // silently stop being able to send or receive anything
        if ($mailbox->is_active) {
            $table = Mailbox::getTable();
            $otherActive = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE is_active = 1 AND id != %d",
                (int) $mailbox->id
            ));
            if ($otherActive === 0) {
                return new \WP_Error('last_mailbox', 'Cannot delete the only active mailbox. Add another mailbox first, or deactivate this one instead.', ['status' => 400]);
            }
        }

        Mailbox::delete($mailbox->id);

        // What happens to this mailbox's emails: keep them unassigned
        // (default) or move them to trash
        $emailsTable = Email::getTable();
        if ($request->get_param('emails') === 'trash') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $emailsTable SET mailbox_id = NULL, status = 'trash' WHERE mailbox_id = %d",
                (int) $mailbox->id
            ));
        } else {
            $wpdb->query($wpdb->prepare(
                "UPDATE $emailsTable SET mailbox_id = NULL WHERE mailbox_id = %d",
                (int) $mailbox->id
            ));
        }

        SyncService::ensureCronSchedule();

        return rest_ensure_response(['message' => 'Mailbox deleted']);
    }

    public function test($request)
    {
        $mailbox = Mailbox::find($request->get_param('id'));
        if (!$mailbox) {
            return new \WP_Error('not_found', 'Mailbox not found', ['status' => 404]);
        }

        $driver = DriverManager::make($mailbox);
        if (is_wp_error($driver)) {
            return $driver;
        }

        $result = $driver->testConnection(Mailbox::settingsOf($mailbox));
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(['success' => true, 'message' => 'Connection verified']);
    }

    public function sync($request)
    {
        $mailbox = Mailbox::find($request->get_param('id'));
        if (!$mailbox) {
            return new \WP_Error('not_found', 'Mailbox not found', ['status' => 404]);
        }

        if (SyncService::isPollingMailbox($mailbox) === false) {
            return rest_ensure_response([
                'success' => true,
                'imported_count' => 0,
                'message' => 'This mailbox receives mail by webhook push; there is nothing to poll.',
            ]);
        }

        $result = SyncService::syncMailbox($mailbox);
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'imported_count' => (int) $result,
            'message' => $result > 0 ? "Imported $result new emails." : 'No new emails found.',
        ]);
    }

    public function drivers($request)
    {
        return rest_ensure_response(DriverManager::driverList());
    }

    /**
     * API representation of a mailbox row: secrets masked, webhook URLs
     * included for push drivers, unread count attached.
     */
    private function present($mailbox, $counts = null)
    {
        $meta = DriverManager::driverMeta($mailbox->driver);
        $isPush = !empty($meta['capabilities']['push']);
        $unread = 0;
        if ($counts !== null && isset($counts[(int) $mailbox->id])) {
            $unread = (int) $counts[(int) $mailbox->id]->unread;
        }

        return [
            'id' => (int) $mailbox->id,
            'name' => $mailbox->name,
            'email' => $mailbox->email,
            'from_name' => $mailbox->from_name,
            'driver' => $mailbox->driver,
            'driver_label' => $meta['label'] ?? $mailbox->driver,
            'capabilities' => $meta['capabilities'] ?? [],
            'category' => $mailbox->category,
            'color' => $mailbox->color,
            'is_active' => (int) $mailbox->is_active,
            'is_default' => (int) $mailbox->is_default,
            'last_synced_at' => $mailbox->last_synced_at,
            'unread' => $unread,
            'driver_settings' => $this->maskedSettings($mailbox),
            'webhook_url' => $isPush ? Mailbox::webhookUrl($mailbox) : null,
            'webhook_url_mime' => ($mailbox->driver === 'mailgun') ? Mailbox::webhookUrl($mailbox, true) : null,
        ];
    }

    private function maskedSettings($mailbox)
    {
        $settings = Mailbox::settingsOf($mailbox);
        unset($settings['inbound_secret']); // Only surfaced inside webhook_url

        foreach (DriverManager::secretFields($mailbox->driver) as $key) {
            if (!empty($settings[$key]) && is_string($settings[$key])) {
                $settings[$key] = $this->mask($settings[$key]);
            }
        }

        return $settings;
    }

    /**
     * Shared validation for store/update. $existing is null on create.
     */
    private function validate($params, $existing)
    {
        $creating = ($existing === null);

        if ($creating || array_key_exists('name', $params)) {
            if (empty($params['name'])) {
                return new \WP_Error('missing_params', 'Mailbox name is required', ['status' => 400]);
            }
        }

        if ($creating || array_key_exists('email', $params)) {
            if (empty($params['email']) || !is_email($params['email'])) {
                return new \WP_Error('invalid_email', 'A valid mailbox email address is required', ['status' => 400]);
            }
        }

        if ($creating || array_key_exists('driver', $params)) {
            if (empty($params['driver']) || !DriverManager::driverMeta($params['driver'])) {
                return new \WP_Error('invalid_driver', 'Unknown mail driver', ['status' => 400]);
            }
        }

        if (array_key_exists('category', $params) && !in_array($params['category'], Mailbox::CATEGORIES, true)) {
            return new \WP_Error('invalid_category', 'Unknown category', ['status' => 400]);
        }

        return true;
    }

    private function mask($string)
    {
        if (strlen($string) < 8) {
            return '********';
        }
        return substr($string, 0, 4) . '********' . substr($string, -4);
    }

    private function isMasked($string)
    {
        return strpos($string, '********') !== false;
    }
}
