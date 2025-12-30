=== Fluent Mailbox ===
Contributors: fluentmailbox
Tags: email, mailbox, aws, ses, smtp, email-client, gmail-like
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern, Gmail-like email client for WordPress powered by AWS SES. Send, receive, and manage emails directly from your WordPress admin panel.

== Description ==

Fluent Mailbox is a powerful email management plugin that transforms your WordPress admin into a modern email client. Built with Vue.js and integrated with AWS SES (Simple Email Service), it provides a seamless email experience similar to Gmail.

= Key Features =

* **Modern Email Interface**: Clean, intuitive Gmail-like interface built with Vue.js
* **AWS SES Integration**: Send and receive emails using Amazon SES
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
* PHP 7.4 or higher
* AWS SES account with verified email/domain
* AWS Access Key ID and Secret Access Key

== Frequently Asked Questions ==

= Do I need an AWS account? =

Yes, Fluent Mailbox requires an AWS account with SES (Simple Email Service) configured. You'll need:
* AWS Access Key ID
* AWS Secret Access Key
* Verified email address or domain in SES

= Can I use this with other email services? =

Currently, Fluent Mailbox only supports AWS SES. Support for other email services may be added in future versions.

= How do I receive emails? =

Fluent Mailbox uses AWS S3 and SNS to receive emails. The plugin will automatically set up the necessary S3 bucket and SNS topic during the inbound setup process.

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
│   │   │   ├── SettingsController.php
│   │   │   └── WebhookController.php
│   │   └── Router.php
│   ├── Models/
│   │   └── Email.php
│   └── Services/
│       ├── AwsSetupService.php
│       ├── InboundService.php
│       └── SesService.php
├── resources/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── App.vue
│       ├── main.js
│       ├── components/
│       │   ├── ComposeModal.vue
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
│       │   └── confetti.js
│       └── views/
│           ├── Drafts.vue
│           ├── EmailDetail.vue
│           ├── Inbox.vue
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

