<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

$subject = trim((string)($data['subject'] ?? ''));
$body = (string)($data['body'] ?? '');
$intervalSec = (float)($data['interval_sec'] ?? 0);
$intervalSec = max(0, min(60, $intervalSec));
$isHtml = (int)($data['is_html'] ?? 1) === 1;

$fromName = trim((string)($data['from_name'] ?? ''));
$fromEmail = trim((string)($data['from_email'] ?? ''));

$smtpUsername = trim((string)($data['smtp_username'] ?? ''));
$smtpPassword = (string)($data['smtp_password'] ?? '');
$smtpHost = trim((string)($data['smtp_host'] ?? 'smtp.gmail.com'));
$smtpPort = (int)($data['smtp_port'] ?? 465);
$smtpPort = max(1, min(65535, $smtpPort));
$smtpSecure = trim((string)($data['smtp_secure'] ?? 'ssl'));

$recipientMode = (string)($data['recipient_mode'] ?? 'selected_all'); // selected_all / selected_random_n
$randomNMode = (string)($data['random_n_mode'] ?? 'fixed'); // fixed / random
$randomN = (int)($data['random_n'] ?? 10);

$copiesMode = (string)($data['copies_mode'] ?? 'fixed'); // fixed / random
$copiesPerRecipient = (int)($data['copies_per_recipient'] ?? 1);
$copiesPerRecipient = max(1, min(50, $copiesPerRecipient));
$copiesMax = (int)($data['copies_max'] ?? $copiesPerRecipient);
$copiesMax = max(1, min(50, $copiesMax));

$selectedIds = $data['recipient_ids'] ?? [];
if (!is_array($selectedIds)) $selectedIds = [];
$selectedIds = array_values(array_unique(array_map('intval', $selectedIds)));
$selectedIds = array_values(array_filter($selectedIds, fn($v) => $v > 0));

if ($subject === '') {
    http_response_code(400);
    echo json_encode(['error' => 'subject required'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($smtpUsername === '' || $smtpPassword === '') {
    http_response_code(400);
    echo json_encode(['error' => 'smtp_username & smtp_password required'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($smtpSecure, ['ssl', 'tls'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'smtp_secure must be ssl or tls'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!is_valid_email($fromEmail)) {
    http_response_code(400);
    echo json_encode(['error' => 'from_email required'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (count($selectedIds) === 0) {
    http_response_code(400);
    echo json_encode(['error' => '請先勾選收件人'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($recipientMode, ['selected_all', 'selected_random_n'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'recipient_mode invalid'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($randomNMode, ['fixed', 'random'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'random_n_mode invalid'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($randomN < 1) $randomN = 1;
if (!in_array($copiesMode, ['fixed', 'random'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'copies_mode invalid'], JSON_UNESCAPED_UNICODE);
    exit;
}

$jobsDir = __DIR__ . '/jobs';
if (!is_dir($jobsDir)) {
    mkdir($jobsDir, 0777, true);
}

$jobId = bin2hex(random_bytes(16));
$jobPath = $jobsDir . '/' . $jobId . '.json';

$payload = [
    'created_at' => date('c'),
    'subject' => $subject,
    'body' => $body,
    'interval_sec' => $intervalSec,
    'is_html' => $isHtml,
    'from_name' => $fromName,
    'from_email' => $fromEmail,
    'recipient_ids' => $selectedIds,
    'recipient_mode' => $recipientMode,
    'random_n_mode' => $randomNMode,
    'random_n' => $randomN,
    'copies_mode' => $copiesMode,
    'copies_per_recipient' => $copiesPerRecipient,
    'copies_max' => $copiesMax,
    'smtp' => [
        'host' => $smtpHost,
        'username' => $smtpUsername,
        'password' => $smtpPassword,
        'port' => $smtpPort,
        'secure' => $smtpSecure,
    ],
];

file_put_contents($jobPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo json_encode(['job' => $jobId], JSON_UNESCAPED_UNICODE);

