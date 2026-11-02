<?php
/**
 * Prime Cleaning Team – contact form mailer.
 * Receives the website form (POST) and emails a copy of the enquiry.
 * Works on HostPapa (PHP mail). It does NOT run on GitHub Pages.
 */

// ---- Settings ----
$TO        = 'info@primecleaningteam.com.au';      // where enquiries are delivered
$FROM      = 'website@primecleaningteam.com.au';   // must be an address on your own domain
$SUBJECT   = 'New enquiry – Prime Cleaning website';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Honeypot: real visitors leave this hidden field empty
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

function clean($key, $max = 2000) {
    $value = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
    $value = str_replace(["\r", "\0"], '', $value);
    return mb_substr(strip_tags($value), 0, $max);
}

$fields = [
    'Name'    => clean('name', 120),
    'Company' => clean('company', 160),
    'Phone'   => clean('phone', 40),
    'Email'   => clean('email', 160),
    'Service' => clean('service', 120),
    'Suburb'  => clean('suburb', 120),
    'Message' => clean('message', 4000),
];

if ($fields['Name'] === '' || $fields['Phone'] === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Name and phone are required']);
    exit;
}

$body = "New enquiry from the website\n\n";
foreach ($fields as $label => $value) {
    if ($value !== '') {
        $body .= str_pad($label . ':', 10) . $value . "\n";
    }
}
$body .= "\nSent: " . date('Y-m-d H:i') . "\nIP: " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";

$headers  = "From: Prime Cleaning Website <{$FROM}>\r\n";
$replyTo  = filter_var($fields['Email'], FILTER_VALIDATE_EMAIL);
if ($replyTo) {
    $headers .= "Reply-To: {$replyTo}\r\n";
}
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($TO, '=?UTF-8?B?' . base64_encode($SUBJECT) . '?=', $body, $headers, '-f' . $FROM);

echo json_encode(['ok' => (bool) $sent]);
