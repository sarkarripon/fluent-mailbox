=== Fluent Mailbox ===
Contributors: fluentmailbox
Tags: email, mailbox, imap, smtp, ses, mailgun, postmark, email-client, gmail-like
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern, Gmail-like email client for WordPress. Connect any mailbox via IMAP/SMTP, Amazon SES, Mailgun, Postmark, or Elastic Email — and manage multiple mailboxes from your admin panel.

== Description ==

Fluent Mailbox is a powerful email management plugin that transforms your WordPress admin into a modern email client. Built with Vue.js, it connects to the mailboxes you already own — cPanel/hosting email, Zoho, or Gmail via IMAP/SMTP, plus Amazon SES, Mailgun, Postmark, and Elastic Email — and provides a seamless email experience similar to Gmail.

= Key Features =

* **Modern Email Interface**: Clean, intuitive Gmail-like interface built with Vue.js
* **Multiple Providers**: Connect via IMAP/SMTP (cPanel, Zoho, Gmail presets), Amazon SES, Mailgun, Postmark, or Elastic Email
* **Multiple Mailboxes**: Manage marketing@, business@, support@ side by side, each with its own connection, unread counts, and sidebar switcher
* **Email Management**:
  * Inbox, Sent, Drafts, and Trash folders
  * Read/unread status tracking
  * Email starring/favorites
  * Bulk actions (mark as read, delete)
* **Compose & Send**:
  * Rich text editor (WordPress TinyMCE)
  * CC and BCC support
  * File attachments
  * Email signatures
  * Email templates
  * Auto-save drafts
* **Advanced Features**:
  * Real-time search with filters
  * Date range filtering
  * Sender filtering
  * Attachment filtering
  * Multiple sort options
  * Keyboard shortcuts
  * Responsive design
* **Smart Organization**:
  * Unread email badges
  * Email snippets/previews
  * Relative date formatting
  * Quick actions on hover
* **User Experience**:
  * Compact mode toggle
  * Drag-to-resize editor
  * Flexible editor height
  * Hidden scrollbars for clean UI
  * Celebration animations
  * Contextual tooltips

= Technical Details =

* Built with Vue.js 3 (Composition API)
* Uses Pinia for state management
* Tailwind CSS for styling
* WordPress REST API for backend
* AWS SDK for PHP integration
* Responsive and mobile-friendly

== Installation ==

= Automatic Installation =

1. Go to WordPress Admin → Plugins → Add New
2. Search for "Fluent Mailbox"
3. Click "Install Now" and then "Activate"

= Manual Installation =

1. Upload the `fluent-mailbox` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Fluent Mailbox in the admin menu

= Requirements =

* WordPress 5.8 or higher
* PHP 8.0 or higher
* At least one email connection: an IMAP/SMTP mailbox (cPanel/hosting email, Zoho, Gmail with an app password), or an Amazon SES, Mailgun, Postmark, or Elastic Email account

== Frequently Asked Questions ==

= Do I need an AWS account? =

No. An AWS account is only needed if you choose the Amazon SES driver. You can instead connect any mailbox you already own via IMAP/SMTP (cPanel/hosting email, Zoho, Gmail with an app password), or use Mailgun or Postmark.

= Can I use this with other email services? =

Yes. Fluent Mailbox supports IMAP/SMTP (works with most providers), Amazon SES, Mailgun, Postmark, and Elastic Email. Third-party drivers can be registered via the `fluent_mailbox_drivers` filter. Gmail/Microsoft OAuth2 is planned for a future version.

= Can I connect more than one mailbox? =

Yes. Add as many mailboxes as you like (e.g. marketing@, business@, support@), each with its own provider and credentials. The sidebar lets you switch between them or view all inboxes together, and composing lets you pick the "From" mailbox.

= How do I receive emails? =

IMAP mailboxes are polled every 5 minutes via WP-Cron. Amazon SES uses S3 and SNS (the plugin sets up the bucket, topic, and receipt rule for you). Mailgun, Postmark, and Elastic Email deliver via secured per-mailbox webhooks.

= How are inbound attachments protected? =

Attachment files from received emails are stored under `uploads/fluent-mailbox-private/` in randomized, unlisted directories with randomized filenames, registered as private media, and served only to logged-in administrators through an authenticated download endpoint. They are deleted together with their email. The directory ships a deny-all `.htaccess`; on nginx (which ignores `.htaccess`) add a rule such as `location ~ ^/wp-content/uploads/fluent-mailbox-private/ { deny all; }` for defense in depth.

= Are emails stored in WordPress database? =

Yes, emails are stored in a custom database table (`wp_fluent_mailbox_emails`) for quick access and management.

= Can I export emails? =

Email export functionality is available through the email detail view. You can download individual emails and their attachments.

= Is there a limit on email storage? =

Email storage is limited by your WordPress database size. For large volumes, consider regular cleanup of old emails or moving them to trash.

= Does it support email attachments? =

Yes! You can attach files when composing emails, and view/download attachments from received emails.

= Can I use email templates? =

Yes, Fluent Mailbox includes an email templates system. Create reusable templates for common email types.

= Are keyboard shortcuts available? =

Yes! Use keyboard shortcuts for faster navigation:
* `C` or `Ctrl/Cmd + N` - Compose new email
* `Ctrl/Cmd + K` - Focus search
* `Ctrl/Cmd + ,` - Open settings

== Screenshots ==

1. Modern inbox interface with email list
2. Compose modal with rich text editor
3. Email detail view with attachments
4. Settings page for AWS configuration
5. Filters and search functionality
6. Drafts management

== Changelog ==

= 1.1.2 =
* New driver: Elastic Email — send via the v4 API, receive via an Inbound Route notification webhook
* Inbound emails now persist attachments (stored as protected media) and CC recipients — for every driver
* Inbound HTML is sanitized before storage (scripts and event handlers stripped)

= 1.1.1 =
* SES configuration now lives entirely on the mailbox record (single source of truth); legacy AWS options are read only once by the upgrade migration
* AWS setup wizard in Settings now creates/updates the SES mailbox record, including the per-mailbox SNS webhook URL
* Connection status is now derived from active mailboxes

= 1.1.0 =
* Multi-provider driver architecture: IMAP/SMTP (with cPanel, Zoho, and Gmail presets), Amazon SES, Mailgun, and Postmark
* Multiple mailboxes, each with its own connection, category, and color
* New Mailboxes screen: add/edit with per-driver forms, test connection, sync now, delete with keep-or-trash choice
* Sidebar mailbox switcher with per-mailbox unread counts; all folder views filter by mailbox
* Compose "From" picker; replies default to the mailbox the original arrived in
* Unified secured webhook endpoint per mailbox (`/webhook/{driver}/{mailbox_id}?secret=...`) with per-mailbox secrets and provider signature verification
* Mailgun inbound now ingests the full raw MIME message instead of rebuilt parsed fields (attachment and CC storage arrived in 1.1.2)
* Per-mailbox de-duplication so a message delivered to several mailboxes appears in each
* Reliable background sync: WP-Cron event registered on a 5-minute schedule while polling mailboxes exist
* Existing AWS SES installs are migrated automatically to a SES mailbox
* Minimum PHP version raised to 8.0

= 1.0.0 =
* Initial release
* AWS SES integration for sending emails
* AWS S3/SNS integration for receiving emails
* Modern Vue.js interface
* Email management (Inbox, Sent, Drafts, Trash)
* Rich text editor with WordPress TinyMCE
* CC/BCC support
* File attachments
* Email signatures and templates
* Auto-save drafts
* Advanced search and filtering
* Keyboard shortcuts
* Responsive design
* Bulk actions
* Email starring/favorites
* Unread indicators and badges

== Upgrade Notice ==

= 1.1.2 =
Adds the Elastic Email driver.

= 1.1.1 =
Finishes the move of SES settings into the mailbox record. Requires PHP 8.0+.

= 1.1.0 =
Major update: multiple mailboxes and new providers (IMAP/SMTP, Mailgun, Postmark). Requires PHP 8.0+. Existing AWS SES setups are migrated automatically — no action needed.

= 1.0.0 =
Initial release of Fluent Mailbox. Make sure you have AWS SES credentials ready before activating.

== Development ==

= Building Assets =

The plugin uses Vite for asset bundling. To build for development:

\`\`\`bash
npm install
npm run dev
\`\`\`

For production:

\`\`\`bash
npm run build
\`\`\`

= File Structure =

\`\`\`
fluent-mailbox/
├── app/
│   ├── Common/
│   │   └── DatabaseMigration.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AttachmentController.php
│   │   │   ├── MailController.php
│   │   │   ├── MailboxController.php
│   │   │   ├── SettingsController.php
│   │   │   └── WebhookController.php
│   │   └── Router.php
│   ├── Models/
│   │   ├── Email.php
│   │   ├── Mailbox.php
│   │   └── Tag.php
│   └── Services/
│       ├── AwsSetupService.php
│       ├── Contracts/
│       │   └── MailDriverInterface.php
│       ├── DriverManager.php
│       ├── Drivers/
│       │   ├── ElasticEmailDriver.php
│       │   ├── ImapSmtpDriver.php
│       │   ├── MailgunDriver.php
│       │   ├── PostmarkDriver.php
│       │   └── SesDriver.php
│       ├── InboundService.php
│       ├── Logger.php
│       ├── SesService.php
│       └── SyncService.php
├── resources/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── App.vue
│       ├── main.js
│       ├── components/
│       │   ├── ComposeModal.vue
│       │   ├── TagManager.vue
│       │   ├── TagPicker.vue
│       │   ├── Tooltip.vue
│       │   └── WpEditor.vue
│       ├── composables/
│       │   ├── useEmailCounts.js
│       │   └── useKeyboardShortcuts.js
│       ├── directives/
│       │   └── clickOutside.js
│       ├── stores/
│       │   └── useAppStore.js
│       ├── utils/
│       │   ├── api.js
│       │   ├── confetti.js
│       │   └── date.js
│       └── views/
│           ├── Drafts.vue
│           ├── EmailDetail.vue
│           ├── Inbox.vue
│           ├── Mailboxes.vue
│           ├── Sent.vue
│           ├── Settings.vue
│           └── Trash.vue
└── fluent-mailbox.php
\`\`\`

== Support ==

For support, feature requests, or bug reports, please visit the plugin's support page or GitHub repository.

== Credits ==

Built with:
* Vue.js 3
* Pinia
* Tailwind CSS
* AWS SDK for PHP
* WordPress REST API

