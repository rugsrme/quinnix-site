<?php
/**
 * Quinnix Software — contact form handler
 *
 * Handles POST submissions from the contact form in index.html.
 * Returns JSON: { "success": bool, "message": string }
 *
 * Config file (not in repository): config.php in the same directory.
 * See config.sample.php for required constants.
 */

declare(strict_types=1);

// Accept only POST, only via XHR (defence-in-depth; not a primary guard)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function json_exit(bool $success, string $message, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// ----------------------------------------------------------------
// Load site config
// ----------------------------------------------------------------
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    error_log('Quinnix contact: config.php not found at ' . $configFile);
    json_exit(false, 'Server configuration is not complete. Please contact via email directly.', 500);
}
require $configFile;

if (!defined('CONTACT_TO') || empty(CONTACT_TO)) {
    error_log('Quinnix contact: CONTACT_TO is not configured.');
    json_exit(false, 'Server configuration is not complete. Please contact via email directly.', 500);
}

// ----------------------------------------------------------------
// Honeypot — must be empty
// ----------------------------------------------------------------
$honeypot = $_POST['website'] ?? '';
if ($honeypot !== '') {
    // Silently accept so bots don't know they were caught
    json_exit(true, 'Your message was sent. I\'ll be in touch soon.');
}

// ----------------------------------------------------------------
// Collect and sanitise inputs
// ----------------------------------------------------------------
$name    = trim(strip_tags($_POST['name']    ?? ''));
$email   = trim(strip_tags($_POST['email']   ?? ''));
$subject = trim(strip_tags($_POST['subject'] ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));

// ----------------------------------------------------------------
// Server-side validation
// ----------------------------------------------------------------
if ($name === '' || mb_strlen($name) > 100) {
    json_exit(false, 'Please provide your name (max 100 characters).', 422);
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
    json_exit(false, 'Please provide a valid email address.', 422);
}

if (mb_strlen($message) < 10 || mb_strlen($message) > 4000) {
    json_exit(false, 'Message must be between 10 and 4000 characters.', 422);
}

// Allowed subject values (empty = not selected)
$allowedSubjects = ['', 'custom-development', 'linux-help', 'automation', 'project-question', 'general'];
if (!in_array($subject, $allowedSubjects, true)) {
    $subject = '';
}

// ----------------------------------------------------------------
// Rate limiting (per IP, file-based, simple)
// ----------------------------------------------------------------
$rateLimit  = defined('CONTACT_RATE_LIMIT') ? (int) CONTACT_RATE_LIMIT : 60;
$ip         = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$safeIp     = preg_replace('/[^a-fA-F0-9:\.]/', '', $ip);
$rateFile   = sys_get_temp_dir() . '/quinnix_contact_rate_' . md5($safeIp);

if (file_exists($rateFile)) {
    $lastTime = (int) file_get_contents($rateFile);
    if (time() - $lastTime < $rateLimit) {
        json_exit(false, 'Please wait a moment before sending another message.', 429);
    }
}
file_put_contents($rateFile, (string) time());

// ----------------------------------------------------------------
// Build email
// ----------------------------------------------------------------
$subjectLabels = [
    'custom-development' => 'Custom Software Development',
    'linux-help'         => 'Linux Help or Consulting',
    'automation'         => 'Automation or AI Project',
    'project-question'   => 'Question About a Project',
    'general'            => 'General Inquiry',
];
$subjectLabel = $subjectLabels[$subject] ?? 'General Inquiry';

$toName = defined('CONTACT_TO_NAME') ? CONTACT_TO_NAME : 'Quinnix Software';

$emailSubject = "Quinnix Contact — {$subjectLabel}";

$emailBody  = "New contact form submission from quinnix.com\n";
$emailBody .= str_repeat('-', 50) . "\n\n";
$emailBody .= "Name:    {$name}\n";
$emailBody .= "Email:   {$email}\n";
$emailBody .= "Subject: {$subjectLabel}\n\n";
$emailBody .= "Message:\n{$message}\n\n";
$emailBody .= str_repeat('-', 50) . "\n";
$emailBody .= "Sent: " . date('Y-m-d H:i:s T') . "\n";
$emailBody .= "IP:   {$ip}\n";

// Let the server set From/envelope naturally (cPanel overrides it anyway).
// Reply-To is the form submitter so replies go directly to them.
$headers  = "Reply-To: {$name} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// ----------------------------------------------------------------
// Send
// ----------------------------------------------------------------
$sent = mail(CONTACT_TO, $emailSubject, $emailBody, $headers);

if (!$sent) {
    error_log("Quinnix contact: mail() failed for {$email}");
    json_exit(false, 'Could not send your message due to a server error. Please try emailing directly.', 500);
}

json_exit(true, 'Your message was sent. I\'ll be in touch soon.');
