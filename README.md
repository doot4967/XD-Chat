# XD Chat

XD Chat is a self-hosted live chat platform for website owners. A user can register, add websites, generate a website/widget key, install the widget script on a website, and reply to visitors from the dashboard.

## Feature Freeze Notice

**Chat module is feature-frozen. Only bug fixes allowed until real-user testing.**

New chat features should stay on hold until the current widget and dashboard chat flow is tested with real users.

## Project Overview

XD Chat is being built as a secure, fast, modern, SaaS-ready live chat platform. The current focus is a production-quality chat module with user isolation, embeddable widget, visitor conversations, file sharing, message actions, presence, typing indicators, notifications, and dashboard controls.

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
  super-admin/              Future super admin module placeholder
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

- Live dashboard chat with polling.
- Widget visitor chat with polling.
- `last_message_id` based incremental message loading.
- Smart scroll behavior.
- New message badge/preview behavior.
- Open/closed chat controls.
- Unread badge and unread sorting.
- Visitor info panel with name, email, page URL, referrer, device, and browser.
- Online/offline presence.
- Typing indicators.
- Notification sound on incoming messages.
- Text, emoji, image, document, audio, video, and voice messages.
- Secure download for uploaded files.
- Image preview/lightbox.
- Audio/video playback protection so only one media item plays at a time.
- Reply, copy, and delete-for-me message actions.
- Dashboard chat search and filters.

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

- Real-user testing is pending.
- Super Admin Panel is planned, not completed.
- Subscription/billing system is pending.
- Message text search is currently on hold.
- Quick Replies are on hold until real business workflow is clear.
- Desktop browser notifications are planned but not completed.
- WhatsApp integration is pending.
- AI auto reply/chatbot is pending.
- Multi-agent assignment and routing is pending.
- Analytics depth is pending.
- Production deployment checklist is pending.

## Development Rule

Do not add new chat features during feature freeze. Only fix verified bugs and regressions.
