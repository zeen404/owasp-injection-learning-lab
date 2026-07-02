<?php
/*
 * OWASP A05 — Command Injection Lab (PHP)
 * =============================================
 * ✅ SECURE MODE: Validate + Escape ก่อนส่ง shell
 *
 * วิธีป้องกัน:
 * 1. Whitelist validation: ตรวจสอบว่าเป็น IP จริงๆ
 * 2. escapeshellarg(): escape special characters สำหรับ shell
 */

$output    = '';
$error     = '';
$ip        = '';
$blocked   = false;
$block_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'] ?? '';

    if (!empty($ip)) {
        // ============================================================
        // ✅ SECURE STEP 1: Whitelist Validation
        // ตรวจสอบว่า input เป็น IP address ที่ถูกต้องเท่านั้น
        // ============================================================
        $is_valid_ip = filter_var($ip, FILTER_VALIDATE_IP) !== false;

        if (!$is_valid_ip) {
            $blocked   = true;
            $block_msg = "❌ Input ถูก block! '{$ip}' ไม่ใช่ IP address ที่ถูกต้อง\n\n";
            $block_msg .= "Whitelist validation ป้องกัน OS command ทั้งหมดที่แอบซ่อนอยู่";
        } else {
            // ============================================================
            // ✅ SECURE STEP 2: escapeshellarg() ครอบ argument
            // แม้จะผ่าน IP validation มาแล้ว ก็ยังใส่ escapeshellarg เพิ่ม
            // ============================================================
            $safe_ip = escapeshellarg($ip);
            $command = "ping -c 3 " . $safe_ip . " 2>&1";
            $output  = shell_exec($command);

            if ($output === null) {
                $error = 'Command execution failed.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Command Injection Lab — SECURE</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
    .header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);padding:16px 32px;display:flex;align-items:center;gap:16px}
    .mode-badge{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700;background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3)}
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
    .result-block{margin-top:24px;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .result-block h3{font-size:15px;font-weight:700;margin-bottom:12px;color:#a78bfa}
    .output{font-family:'JetBrains Mono',monospace;font-size:12px;color:#86efac;white-space:pre-wrap;line-height:1.7}
    .blocked{color:#f87171;font-family:'JetBrains Mono',monospace;font-size:13px;padding:16px;background:rgba(239,68,68,.1);border-radius:8px;border:1px solid rgba(239,68,68,.3);white-space:pre-wrap}
    .secure-note{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:14px 16px;margin-top:16px;font-size:13px;color:#86efac;line-height:1.6}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="back-link">← Dashboard</a>
  <span class="title">💻 Command Injection</span>
  <span class="mode-badge">🟢 SECURE</span>
  <a href="/vulnerable.php" class="switch-link">→ ดูโหมด Vulnerable</a>
</div>

<div class="container">
  <div class="card">
    <h2>🟢 Secure: เครื่องมือ Ping (ป้องกันแล้ว)</h2>
    <p class="desc">
      โค้ดนี้ใช้ <strong>Whitelist Validation</strong> และ <strong>escapeshellarg()</strong> —
      ทำให้ OS command injection เป็นไปไม่ได้ แม้จะพยายามใส่ payload ก็ถูก block ทั้งหมด
    </p>

    <!-- ✅ Secure Source Code -->
    <div class="code-block">
      <pre><code><span style="color:#86efac">// ✅ SECURE REQUEST HANDLER (PHP)</span>
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'] ?? '';

    if (!empty($ip)) {
        // ✅ ป้องกันขั้นที่ 1: ตรวจสอบโครงสร้างว่าเป็น IP Address ที่ถูกต้องเท่านั้น (Whitelist)
        $is_valid_ip = filter_var($ip, FILTER_VALIDATE_IP) !== false;

        if (!$is_valid_ip) {
            $blocked   = true;
            $block_msg = "❌ Input ถูก block! ไม่ใช่ IP address ที่ถูกต้อง";
        } else {
            // ✅ ป้องกันขั้นที่ 2: ใช้ escapeshellarg ครอบตัวแปรเพื่อไม่ให้รัน shell commands ซ้อนได้
            $safe_ip = escapeshellarg($ip);
            $command = "ping -c 3 " . $safe_ip . " 2>&1";
            $output  = shell_exec($command);
            
            if ($output === null) {
                $error = 'Command execution failed.';
            }
        }
    }
}</code></pre>
    </div>

    <form method="POST" action="/secure.php">
      <label>ใส่ IP Address:</label>
      <input type="text" name="ip" id="ip-input"
             placeholder="ลองใส่ payload: 127.0.0.1; cat /etc/passwd"
             value="<?= htmlspecialchars($ip) ?>" />
      <br>
      <button type="submit" id="ping-secure-btn">🚀 Ping (Secure)</button>
    </form>

    <?php if ($blocked): ?>
    <div class="result-block">
      <h3>🛡 ถูก Block!</h3>
      <div class="blocked"><?= htmlspecialchars($block_msg) ?></div>
    </div>
    <?php elseif (!empty($output)): ?>
    <div class="result-block">
      <h3>📟 Command Output (ping จริงๆ):</h3>
      <pre class="output"><?= htmlspecialchars($output) ?></pre>
    </div>
    <?php endif; ?>

    <div class="secure-note">
      🛡 <strong>การป้องกัน 2 ชั้น:</strong><br>
      1. <strong>filter_var(FILTER_VALIDATE_IP)</strong> — ตรวจว่าเป็น IP จริงๆ ก่อน<br>
      2. <strong>escapeshellarg()</strong> — escape metacharacters ทั้งหมด (<code>; | &amp; ` $ ( )</code>)<br>
      แม้ข้ามชั้นแรกมาได้ ชั้นที่สองก็ยังป้องกันได้อีกชั้น
    </div>
  </div>
</div>
</body>
</html>
