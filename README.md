# Fluent Mailbox

A modern, Gmail-like email client for WordPress powered by AWS SES. Send, receive, and manage emails directly from your WordPress admin panel.

<img width="1462" height="821" alt="image" src="https://github.com/user-attachments/assets/a048d79e-74e4-446d-a6bf-eb71abfde4da" />



## Details:
**Contributors:** fluentmailbox  
**Tags:** email, mailbox, aws, ses, smtp, email-client, gmail-like  
**Requires at least:** WordPress 5.8  
**Tested up to:** WordPress 6.9  
**Requires PHP:** 7.4+  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## Description

Fluent Mailbox is a powerful email management plugin that transforms your WordPress admin into a modern email client. Built with Vue.js and integrated with AWS SES (Simple Email Service), it provides a seamless email experience similar to Gmail.

### Key Features

* **Modern Email Interface**: Clean, intuitive Gmail-like interface built with Vue.js
* **AWS SES Integration**: Send and receive emails using Amazon SES
* **Email Management**:
  * Inbox, Sent, Drafts, and Trash folders
  * Read/unread status tracking
  * Email starring/favorites
  * Bulk actions (mark as read, delete)
  * Email tags and filtering
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

### Technical Details

* Built with Vue.js 3 (Composition API)
* Uses Pinia for state management
* Tailwind CSS for styling
* WordPress REST API for backend
* AWS SDK for PHP integration
* Responsive and mobile-friendly

## Installation

### Automatic Installation

1. Go to WordPress Admin → Plugins → Add New
2. Search for "Fluent Mailbox"
3. Click "Install Now" and then "Activate"

### Manual Installation

1. Upload the `fluent-mailbox` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Fluent Mailbox in the admin menu

### Requirements

* WordPress 5.8 or higher
* PHP 7.4 or higher
* AWS SES account with verified email/domain
* AWS Access Key ID and Secret Access Key
* Node.js 16+ and npm (for development)

## Development Setup

### Prerequisites

Before you begin, ensure you have the following installed:

* **Node.js** (v16 or higher) - [Download](https://nodejs.org/)
* **npm** (comes with Node.js) or **yarn**
* **Composer** - [Download](https://getcomposer.org/)
* **PHP** 7.4 or higher
* **WordPress** development environment
* **AWS Account** with SES configured

### Initial Setup

1. **Clone or download the plugin** to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone <repository-url> fluent-mailbox
   cd fluent-mailbox
   ```

2. **Install PHP dependencies** using Composer:
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**:
   ```bash
   npm install
   # or
   yarn install
   ```

### Development Workflow

#### Running Development Server

Start the Vite development server with hot module replacement (HMR):

```bash
npm run dev
# or
yarn dev
```

The development server will run on `http://localhost:4005` and automatically reload when you make changes to Vue components or CSS.

**Important:** Make sure your WordPress site is configured to load assets from the development server. The plugin automatically detects the development environment and loads assets accordingly.

#### Building for Production

When you're ready to build production assets:

```bash
npm run build
# or
yarn build
```

This will:
- Compile and minify Vue components
- Process Tailwind CSS
- Optimize assets for production
- Output files to the `assets/` directory

#### Building Plugin ZIP

To create a distributable ZIP file:

```bash
npm run build:zip
# or
yarn build:zip
```

This will:
1. Build production assets
2. Copy necessary files to a build directory
3. Create a ZIP file ready for distribution

### Project Structure

```
fluent-mailbox/
├── app/                          # PHP backend code
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
│   │   ├── Email.php
│   │   └── Tag.php
│   └── Services/
│       ├── AwsSetupService.php
│       ├── InboundService.php
│       ├── Logger.php
│       └── SesService.php
├── resources/                    # Source files
│   ├── css/
│   │   └── style.css            # Tailwind CSS source
│   └── js/
│       ├── App.vue              # Main Vue app component
│       ├── main.js             # Entry point
│       ├── components/         # Vue components
│       │   ├── ComposeModal.vue
│       │   ├── TagManager.vue
│       │   ├── TagPicker.vue
│       │   ├── Tooltip.vue
│       │   └── WpEditor.vue
│       ├── composables/        # Vue composables
│       │   ├── useEmailCounts.js
│       │   └── useKeyboardShortcuts.js
│       ├── directives/         # Vue directives
│       │   └── clickOutside.js
│       ├── stores/             # Pinia stores
│       │   └── useAppStore.js
│       ├── utils/              # Utility functions
│       │   ├── api.js
│       │   └── confetti.js
│       └── views/              # Vue page components
│           ├── Drafts.vue
│           ├── EmailDetail.vue
│           ├── Inbox.vue
│           ├── Sent.vue
│           ├── Settings.vue
│           └── Trash.vue
├── assets/                      # Built assets (generated)
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── vendor/                      # Composer dependencies
├── node_modules/                # npm dependencies
├── fluent-mailbox.php          # Main plugin file
├── package.json                # Node.js dependencies
├── composer.json               # PHP dependencies
├── vite.config.js              # Vite configuration
├── tailwind.config.js          # Tailwind CSS configuration
├── postcss.config.js           # PostCSS configuration
└── README.md                    # This file
```

### Technology Stack

#### Frontend
* **Vue.js 3** - Progressive JavaScript framework
* **Pinia** - State management for Vue
* **Vue Router** - Client-side routing
* **Tailwind CSS** - Utility-first CSS framework
* **Vite** - Next-generation frontend build tool
* **Axios** - HTTP client
* **Heroicons** - Icon library

#### Backend
* **WordPress REST API** - Backend API endpoints
* **AWS SDK for PHP** - AWS service integration
* **Mail MIME Parser** - Email parsing library

### Development Tips

1. **Hot Module Replacement**: The Vite dev server provides instant feedback. Changes to Vue components will update without a full page reload.

2. **WordPress Environment**: Make sure your WordPress installation is set up for development. The plugin checks for `WP_ENV` constant or defaults to production mode.

3. **API Endpoints**: All API endpoints are prefixed with `/wp-json/fluent-mailbox/v1/`. You can test these endpoints directly or through the Vue app.

4. **Database Migrations**: The plugin automatically runs database migrations on activation and admin_init. Check `app/Common/DatabaseMigration.php` for schema changes.

5. **Debugging**: 
   - Use browser DevTools for frontend debugging
   - Check WordPress debug log for PHP errors
   - Enable WordPress debug mode: `define('WP_DEBUG', true);`

6. **AWS Configuration**: 
   - Set up AWS credentials in the Settings page
   - Verify your email/domain in AWS SES
   - Configure S3 bucket and SNS topic for inbound emails

### Code Style

* **JavaScript/Vue**: Follow Vue.js style guide and use ESLint if configured
* **PHP**: Follow WordPress coding standards
* **CSS**: Use Tailwind utility classes, avoid custom CSS when possible

## Frequently Asked Questions

### Do I need an AWS account?

Yes, Fluent Mailbox requires an AWS account with SES (Simple Email Service) configured. You'll need:
* AWS Access Key ID
* AWS Secret Access Key
* Verified email address or domain in SES

### Can I use this with other email services?

Currently, Fluent Mailbox only supports AWS SES. Support for other email services may be added in future versions.

### How do I receive emails?

Fluent Mailbox uses AWS S3 and SNS to receive emails. The plugin will automatically set up the necessary S3 bucket and SNS topic during the inbound setup process.

### Are emails stored in WordPress database?

Yes, emails are stored in a custom database table (`wp_fluent_mailbox_emails`) for quick access and management.

### Can I export emails?

Email export functionality is available through the email detail view. You can download individual emails and their attachments.

### Is there a limit on email storage?

Email storage is limited by your WordPress database size. For large volumes, consider regular cleanup of old emails or moving them to trash.

### Does it support email attachments?

Yes! You can attach files when composing emails, and view/download attachments from received emails.

### Can I use email templates?

Yes, Fluent Mailbox includes an email templates system. Create reusable templates for common email types.

### Are keyboard shortcuts available?

Yes! Use keyboard shortcuts for faster navigation:
* `C` or `Ctrl/Cmd + N` - Compose new email
* `Ctrl/Cmd + K` - Focus search
* `Ctrl/Cmd + ,` - Open settings

## Screenshots

1. Modern inbox interface with email list
2. Compose modal with rich text editor
3. Email detail view with attachments
4. Settings page for AWS configuration
5. Filters and search functionality
6. Drafts management

## Changelog

### 1.0.0
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
* Email tags and tag management
* Workflow management
* Internal notes system

## Upgrade Notice

### 1.0.0
Initial release of Fluent Mailbox. Make sure you have AWS SES credentials ready before activating.

## Support

For support, feature requests, or bug reports, please visit the plugin's support page or GitHub repository.

## Credits

Built with:
* Vue.js 3
* Pinia
* Tailwind CSS
* AWS SDK for PHP
* WordPress REST API
* Vite
* Mail MIME Parser

## License

GPLv2 or later - https://www.gnu.org/licenses/gpl-2.0.html
