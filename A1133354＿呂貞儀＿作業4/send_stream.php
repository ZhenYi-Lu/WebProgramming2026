<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/mailer.php';

@set_time_limit(0);
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Accel-Buffering: no');

function sse_send(string $event, string $data): void
{
    echo "event: {$event}\n";
    $lines = preg_split("/\r\n|\n|\r/", $data);
    foreach ($lines as $line) {
        echo 'data: ' . $line . "\n";
    }
    echo "\n";
    @ob_flush();
    @flush();
}

$job = (string)($_GET['job'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $job)) {
    sse_send('log', 'Invalid job id');
    sse_send('done', 'failed');
    exit;
}

$jobPath = __DIR__ . '/jobs/' . $job . '.json';
if (!is_file($jobPath)) {
    sse_send('log', 'Job not found');
    sse_send('done', 'failed');
    exit;
}

$jobData = json_decode((string)file_get_contents($jobPath), true);
if (!is_array($jobData)) {
    sse_send('log', 'Invalid job data');
    sse_send('done', 'failed');
    exit;
}

$smtp = (array)($jobData['smtp'] ?? []);
if (empty($smtp['host']) || empty($smtp['username']) || !array_key_exists('password', $smtp)) {
    sse_send('log', 'SMTP 設定不完整');
    sse_send('done', 'failed');
    exit;
}

$fromEmail = trim((string)($jobData['from_email'] ?? ''));
$fromName = trim((string)($jobData['from_name'] ?? ''));
if (!is_valid_email($fromEmail)) {
    sse_send('log', 'From email 不合法');
    sse_send('done', 'failed');
    exit;
}
$smtp['from_email'] = $fromEmail;
$smtp['from_name'] = $fromName;

$subject = (string)($jobData['subject'] ?? '');
$body = (string)($jobData['body'] ?? '');
$intervalSec = (float)($jobData['interval_sec'] ?? 0);
$intervalSec = max(0, min(60, $intervalSec));
$isHtml = (bool)($jobData['is_html'] ?? true);

$recipientMode = (string)($jobData['recipient_mode'] ?? 'selected_all');
$randomNMode = (string)($jobData['random_n_mode'] ?? 'fixed');
$randomN = (int)($jobData['random_n'] ?? 10);
$randomN = max(1, $randomN);

$copiesMode = (string)($jobData['copies_mode'] ?? 'fixed');
$copiesPerRecipient = (int)($jobData['copies_per_recipient'] ?? 1);
$copiesPerRecipient = max(1, min(50, $copiesPerRecipient));
$copiesMax = (int)($jobData['copies_max'] ?? $copiesPerRecipient);
$copiesMax = max(1, min(50, $copiesMax));

$selectedIds = $jobData['recipient_ids'] ?? [];
if (!is_array($selectedIds)) $selectedIds = [];
$selectedIds = array_values(array_unique(array_map('intval', $selectedIds)));
$selectedIds = array_values(array_filter($selectedIds, fn($v) => $v > 0));
if (count($selectedIds) === 0) {
    sse_send('log', '未勾選任何收件人');
    sse_send('done', 'no recipients selected');
    exit;
}

$pdo = db();
$placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
$stmt = $pdo->prepare("SELECT id, email FROM recipients WHERE is_opt_in = 1 AND id IN ({$placeholders}) ORDER BY id ASC");
foreach ($selectedIds as $i => $id) {
    $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
}
$stmt->execute();
$pool = $stmt->fetchAll();

if (count($pool) === 0) {
    sse_send('log', '找不到可寄送的勾選收件人');
    sse_send('done', 'no recipients');
    exit;
}

if ($recipientMode === 'selected_random_n') {
    $poolSize = count($pool);
    if ($randomNMode === 'random') {
        $n = random_int(1, $poolSize);
    } else {
        $n = min($randomN, $poolSize);
    }
    shuffle($pool);
    $recipients = array_slice($pool, 0, $n);
} else {
    $recipients = $pool;
}

$plan = [];
foreach ($recipients as $r) {
    $email = (string)($r['email'] ?? '');
    if (!is_valid_email($email)) {
        continue;
    }
    $copies = $copiesMode === 'random' ? random_int(1, $copiesMax) : $copiesPerRecipient;
    $plan[] = ['email' => $email, 'copies' => $copies];
}

if (count($plan) === 0) {
    sse_send('log', '沒有可寄送的收件人');
    sse_send('done', 'no recipients');
    exit;
}

$sendTotal = array_sum(array_map(fn($x) => (int)$x['copies'], $plan));
sse_send('log', "開始寄送：recipients=" . count($plan) . ", total={$sendTotal}, interval={$intervalSec}s");

try {
    $mail = build_mailer($smtp);
    $mail->SMTPKeepAlive = true;
    $mail->Timeout = 30;
} catch (Throwable $e) {
    sse_send('log', 'Mailer 初始化失敗：' . $e->getMessage());
    sse_send('done', 'failed');
    exit;
}

$sent = 0;
$fail = 0;

foreach ($plan as $item) {
    $email = $item['email'];
    $copies = (int)$item['copies'];

    for ($i = 1; $i <= $copies; $i++) {
        try {
            $mail->clearAddresses();
            $mail->clearCCs();
            $mail->clearBCCs();
            $mail->clearReplyTos();
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->isHTML($isHtml);
            $mail->Body = $body;
            if ($isHtml) {
                $mail->AltBody = strip_tags($body);
            }

            if (!$mail->send()) {
                throw new RuntimeException($mail->ErrorInfo ?: 'send failed');
            }
            sse_send('log', "OK：{$email} ({$i}/{$copies})");
        } catch (Throwable $e) {
            $fail++;
            sse_send('log', "FAIL：{$email} ({$i}/{$copies}) - " . $e->getMessage());
            try {
                $mail->getSMTPInstance()->reset();
            } catch (Throwable $ignored) {
            }
        }

        $sent++;
        $pct = ($sent / $sendTotal) * 100.0;
        sse_send('progress', json_encode(['percent' => $pct, 'sent' => $sent, 'total' => $sendTotal, 'email' => $email], JSON_UNESCAPED_UNICODE));

        if ($intervalSec > 0 && $sent < $sendTotal) {
            usleep((int)round($intervalSec * 1000000));
        }
    }
}

sse_send('done', json_encode(['total' => $sendTotal, 'sent' => $sent, 'fail' => $fail], JSON_UNESCAPED_UNICODE));

