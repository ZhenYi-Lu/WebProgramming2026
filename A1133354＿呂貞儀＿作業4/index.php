<?php
require_once __DIR__ . '/db.php';

$pdo = db();
$count = (int)$pdo->query('SELECT COUNT(*) AS c FROM recipients WHERE is_opt_in = 1')->fetch()['c'];
$recipients = $pdo->query('SELECT id, email, is_opt_in, created_at FROM recipients ORDER BY id DESC LIMIT 500')->fetchAll();
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>大量郵件寄送系統</title>
  <style>
    body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans TC","Microsoft JhengHei",sans-serif;margin:24px;background:#0b1220;color:#e6eefc}
    .wrap{max-width:980px;margin:0 auto}
    .card{background:#101a33;border:1px solid #1f2a4a;border-radius:12px;padding:16px;margin:12px 0}
    h1{font-size:22px;margin:0 0 12px}
    h2{font-size:16px;margin:0 0 12px;color:#bcd0ff}
    label{display:block;font-size:13px;margin:8px 0 4px;color:#bcd0ff}
    input,textarea,select{width:100%;box-sizing:border-box;padding:10px;border-radius:10px;border:1px solid #2a3a66;background:#0c1530;color:#e6eefc}
    textarea{min-height:120px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
    button{padding:10px 14px;border-radius:10px;border:1px solid #2a3a66;background:#2b59ff;color:#fff;cursor:pointer}
    button.secondary{background:#13224a}
    .muted{color:#9bb3e8;font-size:13px}
    .pill{display:inline-block;padding:2px 8px;border-radius:999px;border:1px solid #2a3a66;color:#bcd0ff;font-size:12px}
    .list{max-height:260px;overflow:auto;border:1px solid #1f2a4a;border-radius:10px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px 10px;border-bottom:1px solid #1f2a4a;font-size:13px}
    th{text-align:left;color:#bcd0ff;position:sticky;top:0;background:#101a33}
    progress{width:100%;height:18px}
    pre{white-space:pre-wrap;background:#0c1530;border:1px solid #1f2a4a;border-radius:10px;padding:10px}
    .inline{display:flex;gap:8px;align-items:center}
    .hint{font-size:12px;color:#9bb3e8}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>大量郵件寄送系統 <span class="pill">已同意收件人：<?php echo htmlspecialchars((string)$count, ENT_QUOTES, 'UTF-8'); ?></span></h1>

    <div class="card">
      <h2>A. 收件人</h2>
      <form method="post" action="action_add.php">
        <div class="row">
          <div>
            <label>Email</label>
            <input name="email" placeholder="test@example.com" required />
          </div>
          <div style="display:flex;align-items:flex-end;gap:8px">
            <button type="submit">新增</button>
            <a class="muted" href="index.php">重新整理</a>
          </div>
        </div>
      </form>

      <div class="list">
        <table>
          <thead><tr><th style="width:40px"><input id="ck_all" type="checkbox" /></th><th>No.</th><th>Email</th><th>Opt-in</th><th>建立時間</th></tr></thead>
          <tbody>
          <?php foreach ($recipients as $r): ?>
            <tr>
              <td><input class="ck_rec" type="checkbox" value="<?php echo htmlspecialchars((string)$r['id'], ENT_QUOTES, 'UTF-8'); ?>" /></td>
              <td><?php echo htmlspecialchars((string)$r['id'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string)$r['email'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo ((int)$r['is_opt_in'] === 1) ? 'Y' : 'N'; ?></td>
              <td><?php echo htmlspecialchars((string)$r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <form method="post" action="action_delete.php" onsubmit="return confirm('確定刪除勾選的收件人？');">
        <button type="submit" class="secondary" id="btn_delete">刪除勾選</button>
        <span class="muted">最多顯示 500 筆</span>
      </form>
    </div>

    <div class="card">
      <h2>B. 寄信（只寄給勾選收件人）</h2>

      <div class="row">
        <div>
          <label>收件人模式</label>
          <select id="recipient_mode">
            <option value="selected_all">寄給所有勾選收件人</option>
            <option value="selected_random_n">隨機寄給部分勾選收件人</option>
          </select>
        </div>
        <div>
          <label>隨機收件人數</label>
          <div class="inline">
            <select id="random_n_mode" style="max-width:160px">
              <option value="fixed">固定</option>
              <option value="random">完全隨機</option>
            </select>
            <input id="random_n" type="number" min="1" value="10" />
          </div>
          <div class="hint" id="random_n_hint"></div>
        </div>
      </div>

      <div class="row3">
        <div>
          <label>寄送間隔（秒）</label>
          <input id="interval_sec" type="number" min="0" step="0.1" value="1" />
        </div>
        <div>
          <label>內容格式</label>
          <select id="is_html">
            <option value="1">HTML</option>
            <option value="0">純文字</option>
          </select>
        </div>
        <div style="display:flex;align-items:flex-end;gap:8px">
          <button id="btn_start" type="button">開始寄送</button>
          <button id="btn_stop" type="button" class="secondary" disabled>停止</button>
        </div>
      </div>

      <div class="row">
        <div>
          <label>每位收件人收到封數</label>
          <div class="inline">
            <select id="copies_mode" style="max-width:160px">
              <option value="fixed">固定</option>
              <option value="random">完全隨機</option>
            </select>
            <input id="copies_per_recipient" type="number" min="1" max="50" value="1" />
          </div>
          <div class="hint" id="copies_hint"></div>
        </div>
        <div>
          <label>From</label>
          <div class="inline">
            <input id="from_name" placeholder="Sender name" />
            <input id="from_email" placeholder="sender@example.com" required />
          </div>
        </div>
      </div>

      <div class="row">
        <div>
          <label>主旨</label>
          <input id="subject" value="Here is the subject" />
        </div>
        <div>
          <label>SMTP 帳號 / 密碼</label>
          <div class="inline">
            <input id="smtp_username" placeholder="your@gmail.com" required />
            <input id="smtp_password" type="password" placeholder="App Password" required />
          </div>
        </div>
      </div>

      <div class="row3">
        <div>
          <label>SMTP Host</label>
          <input id="smtp_host" value="smtp.gmail.com" />
        </div>
        <div>
          <label>SMTP Port</label>
          <input id="smtp_port" type="number" min="1" max="65535" value="465" />
        </div>
        <div>
          <label>加密</label>
          <select id="smtp_secure">
            <option value="ssl">ssl</option>
            <option value="tls">tls</option>
          </select>
        </div>
      </div>

      <label>內容</label>
      <textarea id="body">This is the HTML message body &lt;b&gt;in bold!&lt;/b&gt;</textarea>

      <label>寄送進度</label>
      <progress id="prog" value="0" max="100"></progress>
      <div class="muted" id="prog_text">0%</div>

      <label>寄送紀錄</label>
      <pre id="log"></pre>
    </div>
  </div>

<script>
  const logEl = document.getElementById('log');
  const progEl = document.getElementById('prog');
  const progTextEl = document.getElementById('prog_text');
  const startBtn = document.getElementById('btn_start');
  const stopBtn = document.getElementById('btn_stop');
  const ckAll = document.getElementById('ck_all');

  const recipientModeEl = document.getElementById('recipient_mode');
  const randomNModeEl = document.getElementById('random_n_mode');
  const randomNEl = document.getElementById('random_n');
  const randomNHintEl = document.getElementById('random_n_hint');

  const copiesModeEl = document.getElementById('copies_mode');
  const copiesEl = document.getElementById('copies_per_recipient');
  const copiesHintEl = document.getElementById('copies_hint');

  let es = null;

  function log(line) {
    logEl.textContent += line + "\\n";
    logEl.scrollTop = logEl.scrollHeight;
  }

  function setProgress(pct, extra) {
    const p = Math.max(0, Math.min(100, Number(pct || 0)));
    progEl.value = p;
    progTextEl.textContent = `${p.toFixed(1)}%${extra ? " - " + extra : ""}`;
  }

  function selectedRecipientIds() {
    return Array.from(document.querySelectorAll('.ck_rec'))
      .filter(x => x.checked)
      .map(x => parseInt(x.value, 10))
      .filter(n => Number.isFinite(n) && n > 0);
  }

  function refreshUI() {
    const isRandomRecipients = recipientModeEl.value === 'selected_random_n';
    const isRandomN = randomNModeEl.value === 'random';
    randomNModeEl.disabled = !isRandomRecipients;
    randomNEl.disabled = !isRandomRecipients || isRandomN;
    randomNHintEl.textContent = isRandomRecipients
      ? (isRandomN ? '收件人數會在 1 ~ 勾選數 之間隨機' : '')
      : '';

    const isRandomCopies = copiesModeEl.value === 'random';
    copiesHintEl.textContent = isRandomCopies
      ? '每位收件人收到 1 ~ 此欄位數值 之間隨機'
      : '';
  }

  ckAll.addEventListener('change', () => {
    const on = ckAll.checked;
    document.querySelectorAll('.ck_rec').forEach(ck => ck.checked = on);
  });

  document.getElementById('btn_delete').addEventListener('click', (e) => {
    const ids = selectedRecipientIds();
    document.querySelectorAll('input[name="ids[]"]').forEach(n => n.remove());
    const form = e.target.closest('form');
    ids.forEach(id => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'ids[]';
      input.value = String(id);
      form.appendChild(input);
    });
  });

  recipientModeEl.addEventListener('change', refreshUI);
  randomNModeEl.addEventListener('change', refreshUI);
  copiesModeEl.addEventListener('change', refreshUI);
  refreshUI();

  stopBtn.addEventListener('click', async () => {
    stopBtn.disabled = true;
    if (es) {
      es.close();
      es = null;
      log('停止：已關閉事件串流');
    }
  });

  startBtn.addEventListener('click', async () => {
    logEl.textContent = '';
    setProgress(0);

    const ids = selectedRecipientIds();
    if (ids.length === 0) {
      log('請先勾選收件人');
      return;
    }

    startBtn.disabled = true;
    stopBtn.disabled = false;

    const payload = {
      recipient_ids: ids,
      recipient_mode: recipientModeEl.value,
      random_n_mode: randomNModeEl.value,
      random_n: randomNEl.value,
      interval_sec: document.getElementById('interval_sec').value,
      is_html: document.getElementById('is_html').value,
      subject: document.getElementById('subject').value,
      body: document.getElementById('body').value,
      from_name: document.getElementById('from_name').value,
      from_email: document.getElementById('from_email').value,
      smtp_username: document.getElementById('smtp_username').value,
      smtp_password: document.getElementById('smtp_password').value,
      smtp_host: document.getElementById('smtp_host').value,
      smtp_port: document.getElementById('smtp_port').value,
      smtp_secure: document.getElementById('smtp_secure').value,
      copies_mode: copiesModeEl.value,
      copies_per_recipient: copiesEl.value,
      copies_max: copiesEl.value
    };

    try {
      const resp = await fetch('action_send_start.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await resp.json();
      if (!resp.ok) throw new Error(data.error || 'start failed');

      es = new EventSource('send_stream.php?job=' + encodeURIComponent(data.job));
      es.addEventListener('log', (e) => log(e.data));
      es.addEventListener('progress', (e) => {
        const p = JSON.parse(e.data);
        setProgress(p.percent, `${p.sent}/${p.total} ${p.email || ''}`.trim());
      });
      es.addEventListener('done', (e) => {
        log('完成：' + e.data);
        setProgress(100, 'done');
        es.close();
        es = null;
        startBtn.disabled = false;
        stopBtn.disabled = true;
      });
      es.addEventListener('error', () => {
        log('錯誤：事件串流中斷');
        if (es) es.close();
        es = null;
        startBtn.disabled = false;
        stopBtn.disabled = true;
      });
    } catch (err) {
      log('啟動失敗：' + err.message);
      startBtn.disabled = false;
      stopBtn.disabled = true;
    }
  });
</script>
</body>
</html>

