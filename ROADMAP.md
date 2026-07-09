# XD Chat Roadmap

## Feature Freeze Notice

**Chat module is feature-frozen. Only bug fixes allowed until real-user testing.**

This roadmap records the next development direction after the current chat module stabilizes with real users.

## Current Status

The main live chat flow is complete enough for real-user testing:

- Website owner can add website and widget.
- Visitor can chat without login.
- Admin can reply from dashboard.
- User isolation is working.
- Widget security foundation is in place.
- Chat module supports files, voice, emoji, reply, copy, delete-for-me, search, filters, unread, presence, typing, and notifications.

## Stability Checkpoint

Current completed feature set:

- Live chat.
- Widget.
- Typing.
- Presence.
- Notifications.
- File upload.
- Voice.
- Emoji.
- Reply.
- Copy.
- Delete for Me.
- Search/filter.

Checkpoint rule:

- No new chat features during freeze.
- Only verified bug fixes.
- Every bug fix should preserve current send/receive, polling, upload, typing, presence, notification, unread, smart-scroll, reply/copy/delete, and search/filter behavior.

## Phase 1: Stabilization

Goal: test the current completed chat system in real conditions.

- Run multi-user testing.
- Test widget on different browsers.
- Test mobile widget behavior.
- Test slow network behavior.
- Test large file rejection.
- Test audio/video playback.
- Test delete/reply/copy edge cases.
- Test search/filter combinations.
- Test notification behavior after browser interaction.
- Test delete-for-me from both widget and dashboard.
- Fix only bugs found during testing.

## Phase 2: Production Readiness

Goal: prepare the current system for safer launch.

- Add production environment configuration notes.
- Review upload storage permissions.
- Review `.htaccess` protection in upload folders.
- Review PHP error display/log settings.
- Add deployment checklist.
- Add backup/restore notes for database and uploads.
- Add stronger rate limit strategy if needed.
- Add audit logging for important actions.

## Phase 3: Super Admin Panel

Goal: add platform-level management without breaking user isolation.

Planned modules:

- Super admin login/role access.
- Users list and status controls.
- Website/widget overview.
- Chat volume overview.
- Storage usage overview.
- Basic platform analytics.
- Abuse/spam monitoring.
- Account suspension controls.

Security focus:

- Strict role checks.
- No accidental normal-user data leakage.
- Clear separation between user dashboard and super admin panel.

## Phase 4: Chat Enhancements After Real Testing

These are intentionally on hold until real-user testing:

- Quick Replies.
- Message text search.
- Desktop browser notifications.
- Delete for Everyone.
- Contact capture improvements.
- Chat tags/labels.
- Notes for internal admin use.
- Better visitor timeline.
- Export conversation.

## Phase 5: Multi-Agent Support

Goal: allow teams to handle chats.

- Agent accounts under one owner/company.
- Chat assignment.
- Transfer chat.
- Agent online status.
- Agent permissions.
- Internal notes.
- Agent performance analytics.

## Phase 6: Automation and AI

Goal: reduce response time while keeping admin control.

- AI suggested replies.
- AI auto reply when admin is offline.
- Business-hours automation.
- FAQ/chatbot flow.
- Escalation from bot to human.

## Phase 7: Integrations

Goal: connect XD Chat with external business tools.

- WhatsApp integration.
- Email notification integration.
- CRM/webhook integration.
- Slack/Telegram alert integration.
- Payment/subscription gateway.

## Phase 8: SaaS Packaging

Goal: make XD Chat ready as a SaaS product.

- Plans and limits.
- Subscription management.
- Trial/free plan.
- Usage limits.
- Tenant billing.
- Domain verification.
- Production monitoring.

## Priority Order

1. Stability checkpoint documentation.
2. Real-user testing and bug fixes.
3. Production readiness checklist.
4. Super Admin Panel.
5. Chat enhancements based on real feedback.
6. Multi-agent support.
7. AI and automation.
8. Integrations.
9. Subscription/SaaS packaging.

## Hold List

These should not be started until chat feature freeze is lifted:

- Quick Replies.
- Message text search.
- Delete for Everyone.
- Desktop notifications.
- AI auto reply.
- WhatsApp integration.
- Multi-agent routing.
- Subscription system.
