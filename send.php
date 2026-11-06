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

// ---- Text message (SMS) settings – sent through Twilio ----
// Create a Twilio account, buy an SMS-capable number and paste the values here.
// Leave TWILIO_SID empty to disable SMS (email still works).
$TWILIO_SID   = '';                 // Account SID (starts with AC…)
$TWILIO_TOKEN = '';                 // Auth Token
$TWILIO_FROM  = '';                 // Your Twilio number, e.g. '+61400000000'
// Phones that receive each enquiry by SMS: main line + backup copy line.
// Use international format. Empty entries are skipped.
$SMS_TO = [
    '+61452226386',                 // main line (0452 226 386)
    '',                             // backup phone – add the number here
];

date_default_timezone_set('Australia/Melbourne');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Only accept posts sent from this website's own pages
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
$host   = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== $host) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

// Honeypot: real visitors leave this hidden field empty
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

// Privacy Policy consent is required (APP 3 / APP 5)
if (($_POST['consent'] ?? '') !== 'yes') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Consent is required']);
    exit;
}

// Basic flood protection: one enquiry every 30 seconds per visitor
$ipHash   = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . __FILE__);
$lockFile = sys_get_temp_dir() . '/pct_' . $ipHash;
if (is_file($lockFile) && time() - filemtime($lockFile) < 30) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many requests']);
    exit;
}
@touch($lockFile);

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
$body .= "\nConsent to Privacy Policy: yes\nSent: " . date('Y-m-d H:i') . "\n";

$headers  = "From: Prime Cleaning Website <{$FROM}>\r\n";
$replyTo  = filter_var($fields['Email'], FILTER_VALIDATE_EMAIL);
if ($replyTo) {
    $headers .= "Reply-To: {$replyTo}\r\n";
}
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($TO, '=?UTF-8?B?' . base64_encode($SUBJECT) . '?=', $body, $headers, '-f' . $FROM);

// SMS: short summary to each phone in $SMS_TO
$smsSent = false;
if ($TWILIO_SID !== '' && $TWILIO_TOKEN !== '' && $TWILIO_FROM !== '') {
    $sms = "Prime Cleaning enquiry: {$fields['Name']}, {$fields['Phone']}";
    if ($fields['Service'] !== '') $sms .= ", {$fields['Service']}";
    if ($fields['Suburb'] !== '')  $sms .= ", {$fields['Suburb']}";
    $sms = mb_substr($sms, 0, 300);

    foreach ($SMS_TO as $to) {
        if (trim($to) === '') continue;
        $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/{$TWILIO_SID}/Messages.json");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['To' => $to, 'From' => $TWILIO_FROM, 'Body' => $sms]),
            CURLOPT_USERPWD        => $TWILIO_SID . ':' . $TWILIO_TOKEN,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response !== false && $code >= 200 && $code < 300) {
            $smsSent = true;
        }
    }
}

echo json_encode(['ok' => (bool) $sent || $smsSent]);
