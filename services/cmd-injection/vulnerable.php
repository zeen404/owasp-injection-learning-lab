<?php
/*
 * OWASP A05 — Command Injection Lab (PHP)
 * =============================================
 * หน้านี้จำลอง tool "ping" ที่มีช่องโหว่ Command Injection
 *
 * ❌ VULNERABLE: ส่ง user input ไปยัง shell โดยตรง
 *    → ผู้โจมตีสามารถรัน OS command ใดก็ได้บนเซิร์ฟเวอร์
 */

$output = '';
$error  = '';
$ip     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'] ?? '';

    if (!empty($ip)) {
        // ========================================================
        // ❌ VULNERABLE CODE: ไม่มี sanitization ใดๆ
        // ผู้โจมตีสามารถใส่ "127.0.0.1; cat /etc/passwd"
        // และ shell จะ execute ทั้ง ping และ cat /etc/passwd
        // ========================================================
        $command = "ping -c 3 " . $ip . " 2>&1";
        $output  = shell_exec($command);

        if ($output === null) {
            $error = 'Command execution failed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Command Injection Lab — VULNERABLE</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
    .header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);padding:16px 32px;display:flex;align-items:center;gap:16px}
    .mode-badge{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700;background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3)}
    .title{font-size:18px;font-weight:700}
    .switch-link{margin-left:auto;color:#6366f1;text-decoration:none;font-size:13px;font-weight:600}
    .switch-link:hover{text-decoration:underline}
    .back-link{color:#8b949e;text-decoration:none;font-size:13px}
    .container{max-width:900px;margin:0 auto;padding:40px 24px}
    .card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:32px;margin-bottom:24px}
    h2{font-size:20px;font-weight:700;margin-bottom:8px}
    .desc{color:#8b949e;font-size:14px;margin-bottom:24px;line-height:1.6}
    label{display:block;font-size:13px;font-weight:600;color:#8b949e;margin-bottom:6px}
    input[type=text]{width:100%;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:10px 14px;color:#f0f6fc;font-size:14px;font-family:'JetBrains Mono',monospace;outline:none;margin-bottom:16px}
    input[type=text]:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
    button{background:linear-gradient(135deg,#6366f1,#a855f7);border:none;color:#fff;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
    button:hover{opacity:.85}
    .code-block{background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:16px;margin:16px 0;overflow-x:auto}
    code{font-family:'JetBrains Mono',monospace;font-size:12px;color:#7dd3fc}
    .highlight{color:#f97316}
    .payload-hint{font-size:12px;color:#f59e0b;margin-top:6px}
    .result-block{margin-top:24px;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .result-block h3{font-size:15px;font-weight:700;margin-bottom:12px;color:#a78bfa}
    .output{font-family:'JetBrains Mono',monospace;font-size:12px;color:#86efac;white-space:pre-wrap;line-height:1.7}
    .error{color:#f87171;font-size:13px;padding:12px;background:rgba(239,68,68,.1);border-radius:8px;border:1px solid rgba(239,68,68,.2)}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="back-link">← Dashboard</a>
  <span class="title">💻 Command Injection</span>
  <span class="mode-badge">🔴 VULNERABLE</span>
  <a href="/secure.php" class="switch-link">→ ดูโหมด Secure</a>
</div>

<div class="container">
  <div class="card">
    <h2>🔴 Vulnerable: เครื่องมือ Ping</h2>
    <p class="desc">
      โค้ดนี้รับ IP address จากผู้ใช้แล้วส่งไปยัง <strong>shell_exec()</strong> โดยตรง —
      ผู้โจมตีสามารถเพิ่ม <code style="color:#f97316">; command</code> หรือ <code style="color:#f97316">| command</code>
      เพื่อรัน OS command ใดก็ได้บนเซิร์ฟเวอร์
    </p>

    <!-- ❌ Vulnerable Source Code -->
    <div class="code-block">
      <pre><code><span class="highlight">// ❌ VULNERABLE REQUEST HANDLER (PHP)</span>
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'] ?? '';

    if (!empty($ip)) {
        // ❌ ต่อสายสตริงเพื่อรันคำสั่งโดยตรง ทำให้โดน OS Command Injection ได้ง่าย
        $command = "ping -c 3 " . $ip . " 2>&1";
        $output  = shell_exec($command);

        if ($output === null) {
            $error = 'Command execution failed.';
        }
    }
}</code></pre>
    </div>

    <form method="POST" action="/vulnerable.php">
      <label>ใส่ IP Address:</label>
      <input type="text" name="ip" id="ip-input"
             placeholder="ลองใส่: 127.0.0.1; cat /etc/passwd"
             value="<?= htmlspecialchars($ip) ?>" />
      <p class="payload-hint">
        💡 Payloads:
        <code style="color:#f97316">127.0.0.1; cat /etc/passwd</code> |
        <code style="color:#f97316">| whoami</code> |
        <code style="color:#f97316">127.0.0.1 && id</code>
      </p>
      <br>
      <button type="submit" id="ping-btn">🚀 Ping</button>
    </form>

    <?php if (!empty($output) || !empty($error)): ?>
    <div class="result-block">
      <h3>📟 Command Output:</h3>
      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php else: ?>
        <pre class="output"><?= htmlspecialchars($output) ?></pre>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
