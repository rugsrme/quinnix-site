<?php
/**
 * Quinnix Software — contact form configuration
 *
 * SETUP INSTRUCTIONS
 * ------------------
 * 1. Copy this file to config.php in the same directory.
 * 2. Fill in your actual values below.
 * 3. NEVER commit config.php to the repository. It is in .gitignore.
 * 4. Upload config.php to the server manually:
 *      scp -P 2222 config.php quinni5@YOUR_SERVER_HOST:/home/quinni5/quinnix.com/config.php
 *    or create it directly via cPanel File Manager.
 *
 * config.php does NOT need to exist for the static site to work.
 * It is only required for the contact form (contact.php) to send email.
 */

// Recipient — where contact form submissions are delivered
define('CONTACT_TO',          'contact@quinnix.com');   // ← set your actual email

// Display name for the recipient (used in email headers)
define('CONTACT_TO_NAME',     'Quinnix Software');

// Minimum seconds between submissions from the same IP (spam rate limit)
define('CONTACT_RATE_LIMIT',  60);

// SMTP — use the cPanel mailbox credentials for the From address
// Typical cPanel values: host = mail.yourdomain.com, port = 587, secure = 'tls'
define('SMTP_HOST',    'mail.quinnix.com');
define('SMTP_PORT',    587);
define('SMTP_SECURE',  'tls');   // 'tls' = STARTTLS on 587, 'ssl' = port 465
define('SMTP_USER',    'contact@quinnix.com');
define('SMTP_PASS',    'your-mailbox-password');
