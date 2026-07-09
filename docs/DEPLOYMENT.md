# XD Chat Deployment Guide

## Feature Freeze Notice

Chat module is feature-frozen. Only deployment, security, documentation, and verified bug fixes should be done until real-user testing is complete.

## Production Requirements

Recommended stack:

- PHP 8.1 or higher
- MySQL 8 or MariaDB 10.5 or higher
- Apache 2.4 or Nginx 1.20 or higher
- HTTPS enabled
- PHP extensions:
  - `pdo_mysql`
  - `fileinfo`
  - `mbstring`
  - `openssl`
  - `json`

## Application Configuration

Update `config/app.php` on production:

```php
"environment" => "production",
"base_url" => "https://your-domain.com/",
"database" => [
    "host" => "production-db-host",
    "name" => "production-db-name",
    "username" => "production-db-user",
    "password" => "production-db-password",
    "charset" => "utf8mb4"
],
"errors" => [
    "display_errors" => false,
    "log_errors" => true
]
```

Do not commit real production secrets to Git.

## HTTPS Requirements

Production must use HTTPS.

Required:

- Valid SSL certificate.
- `base_url` must start with `https://`.
- Widget script should be embedded with HTTPS URL.
- Secure cookies are enabled automatically in production/HTTPS mode.

## PHP Settings

Recommended `php.ini` values:

```ini
file_uploads = On
upload_max_filesize = 16M
post_max_size = 20M
max_execution_time = 60
max_input_time = 60
memory_limit = 256M
display_errors = Off
log_errors = On
```

Reason:

- XD Chat currently allows videos up to 15 MB.
- `post_max_size` must be larger than `upload_max_filesize`.
- Production should log errors, not display them.

## Upload Protection

Current upload folders:

```text
uploads/
  chat-files/
    images/
    documents/
    audio/
    videos/
```

Current protection:

- `.htaccess` blocks direct access for Apache.
- PHP execution is disabled in upload folders.
- Directory listing is disabled.
- Executable extensions are blocked.
- Files are accessed through secure PHP download endpoints.

Important:

- Apache can use existing `.htaccess` protection.
- Nginx ignores `.htaccess`, so Nginx rules must be configured manually.
- Do not expose `uploads/` as a public static folder.

## Apache Setup

If using Apache with `.htaccess` enabled:

- Ensure `AllowOverride All` or at least rules needed for upload protection.
- Keep existing `.htaccess` files in `uploads/`.
- Ensure `Options -Indexes` is active.

If `.htaccess` is disabled, add equivalent rules in Apache virtual host.

See:

- `docs/server/apache-xd-chat.conf.example`

## Nginx Setup

Nginx does not read `.htaccess`.

You must deny direct web access to uploads:

```nginx
location ^~ /uploads/ {
    deny all;
    return 403;
}
```

See:

- `docs/server/nginx-xd-chat.conf.example`

## Folder Permissions

Recommended Linux permissions:

```text
Project folders: 0755
Project files:   0644
uploads/:        writable by web server user/group
```

Do not set the full project to `777`.

Only these folders need runtime write access:

```text
uploads/chat-files/
uploads/chat-files/images/
uploads/chat-files/documents/
uploads/chat-files/audio/
uploads/chat-files/videos/
```

## Database Setup

Use:

- `database/xd_chat.sql`

This file should contain the complete fresh-install schema.

Do not import test data into production.

## Error Logging

Production:

- `display_errors = Off`
- `log_errors = On`
- App config:
  - `errors.display_errors = false`
  - `errors.log_errors = true`

Make sure the PHP error log path is writable by the server.

## Deployment Checklist

- Set `config/app.php` to production values.
- Import `database/xd_chat.sql`.
- Verify `uploads/chat-files` exists.
- Verify upload folders are writable.
- Verify direct `/uploads/` access returns 403.
- Verify login works.
- Verify dashboard opens.
- Verify website/widget creation works.
- Verify widget loads on allowed domain.
- Verify visitor can send message.
- Verify admin can reply.
- Verify file upload works.
- Verify secure download works.
- Verify PHP errors are logged, not displayed.

## Risks

- If Nginx upload deny rules are missing, uploaded files may be directly accessible.
- If `post_max_size` is too low, uploads may fail before app validation.
- If upload folders are not writable, file/voice/image upload will fail.
- If the whole project is writable by the web server, production security risk increases.
- If HTTPS is not enabled, secure cookies and widget embedding can be unsafe.
