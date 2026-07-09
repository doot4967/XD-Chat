# XD Chat Changelog

## Feature Freeze

**Chat module is feature-frozen. Only bug fixes allowed until real-user testing.**

## Current Development Snapshot

This changelog records the completed state before chat module feature freeze.

## Stability & Documentation Phase

### Documentation

- Added current project status checkpoint.
- Added explicit completed feature list:
  - Live chat
  - Widget
  - Typing
  - Presence
  - Notifications
  - File upload
  - Voice
  - Emoji
  - Reply
  - Copy
  - Delete for Me
  - Search/filter
- Added known limitations section.
- Added next planned features section.
- Updated roadmap priority around stabilization and real-user testing.
- Reconfirmed feature freeze note for chat module.

### Stability Note

- No PHP, JavaScript, CSS, or database logic was changed in this documentation phase.
- Chat module remains feature-frozen.
- Only verified bug fixes should be made until real-user testing is complete.

### Added

- User authentication pages and dashboard structure.
- Website management module.
- Widget management module.
- `widgets` database table connected to users and websites.
- User isolation across websites, widgets, visitors, and chats.
- Live chat dashboard module.
- Public widget module.
- Widget loader and widget API flow.
- Allowed domain validation for widget requests.
- Widget active and website active validation.
- Message length limit and basic rate limiting.
- Visitor pre-chat form.
- Visitor page URL, referrer, browser, and device tracking.
- Incremental polling through `last_message_id`.
- Open/closed chat controls.
- Unread count and unread sorting.
- Visitor info panel in dashboard.
- Online/offline presence.
- Typing indicators.
- Notification sound foundation.
- File and media upload support:
  - Images
  - Documents
  - Audio
  - Video
  - Voice recordings
- Secure upload structure under `uploads/chat-files/`.
- Secure download endpoints for widget and dashboard.
- Image preview and lightbox.
- Audio/video rendering.
- Single media playback behavior.
- Custom emoji picker.
- Reply to message.
- Copy message.
- Delete-for-me message visibility.
- Dashboard chat search by visitor name, email, and website name.
- Open/closed/unread filters with search combination.
- Widget module information page.
- Premium UI polish for auth pages and selected dashboard/widget areas.

### Changed

- Website list label changed from Widget Key to Website Key where needed.
- Dashboard sidebar includes Widgets menu.
- Widget duplicate creation is blocked.
- Chat list sorting prioritizes unread open conversations, then latest activity.
- Closed chats maintain latest activity order.
- Dashboard chat details are collapsed behind a Details button.
- Widget layout constrained to viewport with scrollable message area.
- Dashboard smart scroll prevents unwanted jump-to-bottom while reading older messages.
- Widget smart scroll prevents unwanted jump-to-bottom while visitor reads older messages.
- Attachment menu made reusable for future options.
- Download buttons changed to compact icon-style controls.
- Message action menu redesigned toward WhatsApp-style behavior.
- Delete currently works as delete-for-me through per-side message visibility.

### Fixed

- Chat ownership/user isolation issues.
- Widget table/schema alignment.
- Duplicate widget creation for the same website.
- Widget not appearing due to wrong key/test setup.
- Auto reply behavior was adjusted earlier so repeated automatic messages are avoided.
- Dashboard audio playback reset issue during polling.
- Widget composer overflow and horizontal scroll issues.
- Multiple audio/video playback at the same time.
- Emoji and attachment click behavior in widget.
- Last message action menu positioning near composer.
- Delete modal/action menu overlap.

### Database Summary

Important database additions used by current chat work:

- `widgets`
- `chat_presence`
- `message_deletions`
- `chats.visitor_page_url`
- `chats.visitor_referrer`
- `chats.visitor_browser`
- `chats.visitor_device`
- `chats.last_seen_message_id`
- `chats.closed_at`
- `messages.message_type`
- `messages.reply_to_message_id`
- file metadata columns in `messages`
- future delete-for-everyone columns:
  - `messages.is_deleted`
  - `messages.deleted_at`
  - `messages.deleted_by`

### Known Pending

- Real-user testing.
- Super Admin Panel.
- Production deployment checklist.
- Message text search.
- Quick Replies.
- Desktop notifications.
- Multi-agent support.
- AI auto reply/chatbot.
- WhatsApp integration.
- Subscription/billing system.

### Notes

- No new chat features should be added during the freeze.
- Bug fixes should be focused, tested, and documented.
- Existing working flows must remain backward compatible.
