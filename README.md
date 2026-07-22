# XD Chat

XD Chat is a self-hosted live chat platform for website owners. A user can register, add websites, generate a website/widget key, install the widget script on a website, and reply to visitors from the dashboard.

## Feature Freeze Notice

**Chat module is feature-frozen. Only bug fixes allowed until real-user testing.**

New chat features should stay on hold until the current widget and dashboard chat flow is tested with real users.

## Project Overview

XD Chat is being built as a secure, fast, modern, SaaS-ready live chat platform. The current focus is a production-quality chat module with user isolation, embeddable widget, visitor conversations, file sharing, message actions, presence, typing indicators, notifications, and dashboard controls.

## Current Project Status

The chat module has reached a stability checkpoint. Core live chat, widget, dashboard chat controls, visitor experience, media sharing, message actions, and search/filter features are complete for internal and real-user testing.

Estimated completion at this checkpoint:

- Core Chat MVP: approximately 85% complete.
- Full SaaS vision: approximately 60-65% complete.

Current status:

- Chat module: feature-frozen for real-user testing.
- Widget module: functional with security checks and visitor chat flow.
- Dashboard chat module: functional with conversation controls and message actions.
- Upload/media module: functional with secure upload/download flow.
- Super Admin module: largely complete, including access control, user/status management, platform overviews, audit logs, analytics, and settings; final browser QA and production hardening remain.
- Responsive dashboard/chat/settings changes: implemented locally and awaiting browser regression QA and bug-only stabilization before a clean commit.
- Documentation phase: active checkpoint documentation is being maintained.

## Completed Modules

- Authentication pages: login, register, forgot/reset password flow files are available.
- Dashboard base: dashboard shell, sidebar, header, websites, widgets, visitors, analytics, settings pages.
- Website management: add/edit/delete/list websites with per-user ownership.
- Widget management: add/edit/delete/list widgets, duplicate widget prevention, widget menu in dashboard.
- User isolation: each logged-in user only sees and manages their own websites, widgets, visitors, and chats.
- Widget security foundation: domain validation, active widget/website validation, message length limits, basic rate limiting, reduced loader exposure.
- Widget API cleanup: helper-style JSON responses and optimized message polling with `last_message_id`.
- Visitor experience: pre-chat visitor name/email, page URL, referrer, browser, and device capture.
- Admin chat controls: open/closed chat status, close chat action, unread count, unread sorting, seen tracking.
- Presence foundation: online/offline status for visitor and admin.
- Typing indicator: visitor/admin typing status using existing presence table fields.
- Notification foundation: reusable sound notification logic for new incoming messages.
- File and media sharing: images, documents, audio, video, voice recording, secure upload structure, secure download endpoints.
- Emoji picker: lightweight custom emoji picker with recent/search support.
- Voice recording: click-to-start/click-to-stop recording and audio message reuse.
- Reply and copy: reply preview, quoted reply display, copy feedback.
- Delete for me: current-side message hiding through `message_deletions`.
- Chat search and filters: sidebar search by visitor name, visitor email, and website name with open/closed/unread filters.
- Super Admin foundation: role-protected dashboard, users and status controls, website/widget/chat overviews, audit logs, platform analytics, account settings, and platform settings.

## Current Folder Structure Summary

```text
XD-Chat/
  admin/                    Future/admin area placeholder
  api/                      API area placeholder
  assets/                   Shared frontend CSS and JS
  auth/                     Login, register, logout, password pages
  config/                   Configuration files
  dashboard/                User dashboard modules
    chat/                   Live chat dashboard module
      ajax/                 Chat AJAX endpoints
      assets/               Chat-specific CSS, JS, images
      includes/             Chat UI partials
  database/                 Database connection and SQL schema
  docs/                     Existing internal documentation
  includes/                 Shared PHP templates and reusable functions
  super-admin/              Largely complete platform administration module
  uploads/                  Secured uploaded chat files
    chat-files/
      images/
      documents/
      audio/
      videos/
  widget/                   Public embeddable widget module
    ajax/                   Legacy/support widget AJAX files
```

## Current Chat Features

- Live chat: dashboard and visitor widget conversation flow.
- Widget: embeddable website chat widget with website/widget key validation.
- Typing: visitor and admin typing indicators.
- Presence: visitor/admin online and offline status.
- Notifications: incoming message sound foundation.
- File upload: image, document, audio, and video sharing.
- Voice: click-to-record voice messages using audio message flow.
- Emoji: custom lightweight emoji picker.
- Reply: reply preview before send and quoted reply inside message bubbles.
- Copy: copy text, filename, or secure file link with copied feedback.
- Delete for Me: current-side message hiding using `message_deletions`.
- Search/filter: dashboard sidebar search with open, closed, and unread filters.
- `last_message_id` based incremental message loading.
- Smart scroll behavior for dashboard and widget.
- New message badge/preview behavior.
- Open/closed chat controls.
- Unread badge and unread sorting.
- Visitor info panel with name, email, page URL, referrer, device, and browser.
- Secure download for uploaded files.
- Image preview/lightbox.
- Audio/video playback protection so only one media item plays at a time.

## Database Changes Summary

Base schema includes:

- `users`
- `websites`
- `widgets`
- `chats`
- `messages`
- `chat_presence`

Recent chat-related database changes applied during development:

- `widgets` table added and connected to `user_id` and `website_id`.
- `chats` visitor metadata fields:
  - `visitor_page_url`
  - `visitor_referrer`
  - `visitor_browser`
  - `visitor_device`
- `chats` control fields:
  - `last_seen_message_id`
  - `closed_at`
- `messages` future-ready message fields:
  - `message_type`
  - file path/name/mime/size metadata
  - `reply_to_message_id`
  - `is_deleted`
  - `deleted_at`
  - `deleted_by`
- `chat_presence` table for online/offline and typing state.
- `message_deletions` table for delete-for-me visibility.
- Indexes added for chat status, message sender/chat lookup, deleted state, reply lookup, and message deletion lookup.

## Testing Checklist

- Register/login as two different users.
- Add separate websites for both users.
- Create widgets for both websites.
- Confirm each user sees only their own websites, widgets, visitors, and chats.
- Install each widget key on separate test pages.
- Send visitor messages from both widgets.
- Confirm no chat mixing between users.
- Test admin reply from dashboard.
- Test open/closed chat behavior.
- Test unread count and unread sorting.
- Test visitor details panel.
- Test online/offline status.
- Test typing indicator both ways.
- Test incoming notification sound after user interaction.
- Test image/document/audio/video upload from widget and dashboard.
- Test voice recording from widget and dashboard.
- Test secure file download.
- Test emoji insertion.
- Test reply and copy.
- Test delete-for-me on sent and received messages.
- Test last message action menu.
- Test chat search with open/closed/unread filters.
- Test mobile widget layout.

## Known Pending Items

- Real-user and multi-user testing.
- Cross-browser and responsive regression QA for the current dashboard, chat, Super Admin, and settings changes.
- Desktop browser notifications.
- Message-content search.
- Quick Replies after real business workflow is validated.
- Delete for Everyone.
- Team accounts, chat assignment, transfer, and routing.
- WebSocket-based realtime transport; the current implementation remains polling-based.
- AI suggested replies and controlled auto-reply workflows.
- WhatsApp, email, CRM/webhook, and team-notification integrations.
- Subscription billing, plans, trials, and usage limits.
- Production monitoring, operational alerting, and final launch hardening.

## Known Limitations

- Chat is currently polling-based; WebSocket support is planned for future scale.
- Message text search is not active yet; current search covers visitor name, email, and website name.
- Desktop browser notifications are not fully implemented yet.
- Quick Replies are intentionally on hold until real business usage is observed.
- Delete for Everyone is reserved for future behavior; current active behavior is Delete for Me.
- Super Admin is largely complete but still requires browser regression QA, abuse/spam workflow validation, and production hardening.
- Multi-agent assignment/routing is not implemented yet.
- Production deployment hardening still needs final review.
- Real-user testing has not been completed yet.

## Immediate Next Milestone

Run browser QA on the current responsive dashboard/chat/settings changes, fix verified regressions only, and then prepare one clean commit. New chat features remain on hold during this milestone.

Required checks include:

- Desktop and mobile navigation, focus handling, overlays, and responsive breakpoints.
- Chat send/receive, polling, unread/seen state, typing, presence, and open/close/reopen flows.
- Uploads, voice recording, media playback, reply, copy, and Delete for Me.
- Profile and password settings, Super Admin navigation, and role isolation.
- Multi-user ownership isolation and slow-network/stale-response behavior.

## Later Planned Features

- Real-user testing and bug-only stabilization.
- Message text search after freeze is lifted.
- Quick Replies after real workflow is clear.
- Desktop notifications.
- Delete for Everyone.
- Team accounts, assignment, transfer, and routing.
- WebSocket transport.
- AI suggested replies and controlled auto reply.
- External integrations.
- Subscription billing, plans, trials, and limits.
- Production monitoring and operational hardening.

## Deployment Documents

Production setup notes are available here:

- [Deployment Guide](docs/DEPLOYMENT.md)
- [Production Checklist](docs/PRODUCTION-CHECKLIST.md)
- [Backup and Restore Guide](docs/BACKUP-RESTORE.md)
- [Apache Example](docs/server/apache-xd-chat.conf.example)
- [Nginx Example](docs/server/nginx-xd-chat.conf.example)

## Development Rule

Do not add new chat features during feature freeze. Only fix verified bugs and regressions.
