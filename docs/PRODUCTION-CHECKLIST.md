# XD Chat Production Checklist

Use this checklist before deploying XD Chat to a live server.

## Feature Freeze Notice

- [x] Chat module is feature-frozen. Only bug fixes allowed until real-user testing.

## 1. Pre-Deployment

- [ ] Latest Git backup/push completed.
- [ ] Production domain ready.
- [ ] SSL/HTTPS active.
- [ ] Hosting/server access ready.
- [ ] PHP version verified.
- [ ] MySQL/MariaDB version verified.
- [ ] Required PHP extensions verified:
  - [ ] `pdo_mysql`
  - [ ] `fileinfo`
  - [ ] `mbstring`
  - [ ] `openssl`
  - [ ] `json`

## 2. Production Configuration

- [ ] `config/app.php` environment set to `production`.
- [ ] Real HTTPS `base_url` configured.
- [ ] Production database host configured.
- [ ] Production database name configured.
- [ ] Production database username configured.
- [ ] Production database password configured.
- [ ] `display_errors = false`.
- [ ] `log_errors = true`.
- [ ] Real secrets are not committed to GitHub.

## 3. Database

- [ ] Fresh production database created.
- [ ] `database/xd_chat.sql` imported.
- [ ] All required 7 tables verified:
  - [ ] `users`
  - [ ] `websites`
  - [ ] `widgets`
  - [ ] `chats`
  - [ ] `messages`
  - [ ] `chat_presence`
  - [ ] `message_deletions`
- [ ] Foreign keys verified.
- [ ] Indexes verified.
- [ ] Production database user permissions verified.
- [ ] Production database user does not use root/admin-level access.

## 4. Server Security

- [ ] HTTPS redirect enabled.
- [ ] Secure session cookies verified on HTTPS.
- [ ] Apache upload protection configured if using Apache.
- [ ] Nginx upload protection configured if using Nginx.
- [ ] Directory listing disabled.
- [ ] PHP/script execution blocked inside upload folders.
- [ ] Direct access to `uploads/` blocked.
- [ ] Sensitive config files blocked from public access.
- [ ] Database files blocked from public access.

## 5. PHP Settings

- [ ] `file_uploads = On`.
- [ ] `upload_max_filesize = 16M` or higher.
- [ ] `post_max_size = 20M` or higher.
- [ ] `memory_limit = 256M` or higher.
- [ ] `max_execution_time = 60` or higher.
- [ ] `max_input_time = 60` or higher.
- [ ] `display_errors = Off`.
- [ ] `log_errors = On`.

## 6. File Permissions

- [ ] Project files do not have unnecessary write permission.
- [ ] Upload folders are writable by the web server.
- [ ] Whole project is not set to `777`.
- [ ] Error log folder is writable.
- [ ] `.htaccess` files exist in upload folders on Apache deployments.
- [ ] Nginx deny rules are active on Nginx deployments.

## 7. Functional Testing

- [ ] Register works.
- [ ] Login works.
- [ ] Logout works.
- [ ] Session timeout works.
- [ ] Website add works.
- [ ] Website edit works.
- [ ] Website delete works.
- [ ] Widget add works.
- [ ] Widget edit works.
- [ ] Widget delete works.
- [ ] Widget installation works on production website.
- [ ] Visitor message works.
- [ ] Admin reply works.
- [ ] Text message works.
- [ ] File upload works.
- [ ] Image upload and preview work.
- [ ] Audio upload/playback works.
- [ ] Video upload/playback works.
- [ ] Voice recording works.
- [ ] Emoji picker works.
- [ ] Reply message works.
- [ ] Copy message works.
- [ ] Delete for Me works.
- [ ] Open chats work.
- [ ] Closed chats work.
- [ ] Unread badge works.
- [ ] Search works.
- [ ] Filters work.
- [ ] Typing indicator works.
- [ ] Presence/online status works.
- [ ] Notification sound works after user interaction.
- [ ] Smart scroll works.
- [ ] Mobile widget layout works.
- [ ] Mobile dashboard basic view checked.

## 8. Security Testing

- [ ] User isolation tested with two accounts.
- [ ] User A cannot see User B websites.
- [ ] User A cannot see User B widgets.
- [ ] User A cannot see User B chats.
- [ ] User A cannot download User B files.
- [ ] Invalid CSRF requests rejected.
- [ ] Invalid widget key rejected.
- [ ] Invalid widget domain rejected.
- [ ] Inactive widget rejected.
- [ ] Inactive website rejected.
- [ ] Unsafe file uploads blocked.
- [ ] Direct upload URL blocked.
- [ ] Unauthorized file download blocked.
- [ ] Logged-out dashboard access blocked.
- [ ] Closed chat reply blocked.

## 9. Backup & Recovery

- [ ] Database backup configured.
- [ ] `uploads/chat-files` backup configured.
- [ ] Backup retention documented.
- [ ] Backup storage is outside public web root.
- [ ] Restore process documented.
- [ ] Restore test completed.
- [ ] Restored files keep correct permissions.
- [ ] Restored upload protection verified.

## 10. Post-Launch

- [ ] PHP error logs checked.
- [ ] Failed uploads monitored.
- [ ] Server CPU/RAM usage monitored.
- [ ] Storage usage monitored.
- [ ] Polling/server load monitored.
- [ ] Real-user bugs recorded.
- [ ] Chat module feature freeze maintained.
- [ ] No new chat features added before real-user testing.
- [ ] Backup schedule monitored.
- [ ] Security logs reviewed.

## Related Documentation

- [Deployment Guide](DEPLOYMENT.md)
- [Backup and Restore Guide](BACKUP-RESTORE.md)
- [Apache Example](server/apache-xd-chat.conf.example)
- [Nginx Example](server/nginx-xd-chat.conf.example)
