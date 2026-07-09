# XD Chat Backup and Restore Guide

## Backup Scope

XD Chat backup must include both:

- MySQL database
- Uploaded chat files

Database and uploads must be backed up together because messages reference uploaded file paths.

## Required Backup Items

Database:

```text
xd_chat
```

Upload folders:

```text
uploads/chat-files/images/
uploads/chat-files/documents/
uploads/chat-files/audio/
uploads/chat-files/videos/
```

Configuration:

```text
config/app.php
```

Do not publicly expose backup files.

## Database Backup

Example command:

```bash
mysqldump -u DB_USER -p xd_chat > xd_chat_backup.sql
```

Recommended:

- Run daily database backups.
- Keep at least 7 daily backups.
- Keep weekly/monthly backups for production.
- Store backups outside the public web root.

## Upload Backup

Example command:

```bash
tar -czf xd_chat_uploads_backup.tar.gz uploads/chat-files
```

Recommended:

- Back up uploads daily.
- Keep uploads backup in the same backup set as the database backup.
- Do not store upload backups in a public directory.

## Restore Order

Recommended restore process:

1. Restore project code.
2. Restore `config/app.php`.
3. Restore database.
4. Restore `uploads/chat-files`.
5. Set folder permissions.
6. Verify upload protection.
7. Test login and dashboard.
8. Test widget load.
9. Test secure file download.

## Database Restore

Example command:

```bash
mysql -u DB_USER -p xd_chat < xd_chat_backup.sql
```

If restoring to a fresh server:

1. Create database.
2. Import schema or backup.
3. Verify tables exist.

Required tables:

- `users`
- `websites`
- `widgets`
- `chats`
- `messages`
- `chat_presence`
- `message_deletions`

## Upload Restore

Example command:

```bash
tar -xzf xd_chat_uploads_backup.tar.gz
```

After restore, verify:

- `uploads/chat-files/images`
- `uploads/chat-files/documents`
- `uploads/chat-files/audio`
- `uploads/chat-files/videos`
- `.htaccess` files still exist on Apache servers

## Permissions After Restore

Recommended:

```bash
find uploads/chat-files -type d -exec chmod 755 {} \;
find uploads/chat-files -type f -exec chmod 644 {} \;
```

Then ensure the web server user/group can write to:

```text
uploads/chat-files/
```

Avoid:

```bash
chmod -R 777 .
```

## Restore Testing Checklist

- Login works.
- Dashboard loads.
- Websites page loads.
- Widgets page loads.
- Live Chats page loads.
- Widget loads on website.
- Visitor can send text.
- Admin can reply.
- Old image preview loads.
- Old audio/video plays.
- Document download works.
- Direct upload URL returns 403.
- New upload works.

## Risks

- If database is restored without uploads, old file messages will show missing files.
- If uploads are restored without database, files cannot be linked to messages.
- If permissions are wrong, uploads/downloads may fail.
- If `.htaccess` files are missing on Apache, upload protection may weaken.
- On Nginx, `.htaccess` files do nothing; server deny rules are required.
