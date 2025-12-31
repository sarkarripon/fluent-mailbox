Project Name: Fluent Mailbox - Business email inside business website.

Key Points

Unified Usability
Business email lives directly inside the business website—no need to jump between multiple tools. Mailbox and SMTP work as a single system, so both newsletters and transactional emails have a mailbox. Customer replies are never lost.

Effortless Scalability
Built on AWS-inspired serverless principles (Lambda, S3, SES concepts applied to WordPress), the system scales automatically—from 10 emails per day to 10 million—without reconfiguration or infrastructure headaches.

Business-First Relevance
Solves today’s real problems: rising email costs, vendor lock-in, and lack of data ownership. At the same time, it creates a foundation ready for AI-driven automation, insights, and customer intelligence.

True Ecosystem Fit
This isn’t another external SaaS. It becomes part of the business operating system by integrating natively with WordPress—where content, users, commerce, and communication already live.

1. The Problem
In the modern web ecosystem, businesses especially those running on WordPress, face a critical problem regarding customer communication:

Data Fragmentation: Customer data lives in the website (fluentCart, WooCommerce orders, membership levels), but communication happens in a separate silo (Gmail, Zoho, Help Scout). Support agents switch tabs constantly, lacking context.
The "SaaS Tax": Professional helpdesk solutions are expensive (costing $20-$100+ per agent/month), taxing small businesses for essential functionality.
Privacy & Ownership: Relying on third-party SaaS means handing over sensitive customer emails to external servers, creating privacy risks and vendor lock-in.
Lack of Integration: Most SaaS solutions are siloed, making it hard to connect with other tools (e.g., CRM, marketing platforms). 

2. The Solution:
Fluent Mailbox is a self hosted, cloud powered inbox that turns any WordPress site into a professional email helpdesk/mailbox.

Core Architecture (Current State) 
Instead of processing emails on the hosting server (which is slow and unreliable, email offen land to spam), Fluent Mailbox leverages AWS Infrastructure for enterprise grade performance at near zero cost, with the added benefit of inbox deliveray to most email clients.

Inbound Engine: Uses AWS SES & S3 to receive and parse incoming emails instantly via Webhooks.
Storage: Raw email data is stored securely in S3, keeping the WordPress database light.
Frontend: A modern, Gmail-like Single Page Application (SPA) built with Vue 3, ensuring a fast, app-like experience within the WP Admin.
Cost: Utilizing AWS SES means sending/receiving 100,000 emails costs pennies, compared to hundreds of dollars for SaaS alternatives.

Key Features:

Email Management
- Clean, modern inbox interface with real-time updates
- Advanced search and filtering (by read status, date range, attachments, sender)
- Bulk actions (mark as read, delete multiple emails)
- Sorting options (date, sender, subject)
- Attachment support with preview and download

Workflow Management
- Compact workflow controls accessible via header icon
- Assign emails to team members
- Track workflow status (Open, Pending, Resolved)
- Quick access modal for status updates

Internal Notes
- Team collaboration through internal notes on emails
- Compact single-line display for quick scanning
- Modal-based note creation for clean UX
- Full note text visible on hover
- User attribution with timestamps

Database & Performance
- Automatic migration system ensures all tables exist
- Intelligent table creation for new and existing installations
- Runs on both admin and REST requests to prevent errors
- Lightweight, optimized queries

3. Future Scalability: The "AI-Native" Evolution
The true power of Fluent Mailbox lies in its potential to become an AI-Native Support Agent. Because the data lives inside the WordPress database, we can leverage LLMs (Large Language Models) in ways SaaS tools cannot easily do.

Scalability Roadmap:

Phase 1: Smart Triage & Sentiment Analysis (The "Vibe" Check)

Feature: Auto-analyze incoming emails for sentiment (Angry/Happy/Urgent).
Value: automatically drives "Urgent" or "Refund" requests to the top of the queue. Support agents solve the burning problems first.

Phase 2: Context-Aware "Draft Zero"

Feature: When an email arrives, the AI reads it AND looks up the user's order history, shipping status, and the website's documentation/Knowledge Base.
Value: It pre-drafts a reply: "Hi [Name], I see your order #123 is currently in transit in New York...". The human agent just reviews and clicks "Send." Reduces response time by 80%.

Phase 3: Autonomous Agent Mode

Feature: For simple queries ("Where is my order?", "Refunding policy?"), the AI can autonomously reply and close the ticket.
Value: 24/7 distinct support without hiring night shifts.

