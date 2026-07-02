<?php
/*
 * OWASP A05 — XPath Injection Lab (PHP)
 * ======================================
 * ❌ VULNERABLE MODE
 *
 * โปรแกรม Login ที่ใช้ XML file เก็บ user data
 * และใช้ XPath query ในการตรวจสอบ — แต่ไม่มี sanitization!
 */

$result   = null;
$error    = '';
$username = '';
$password = '';
$query    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username !== '' || $password !== '') {
        $xml = simplexml_load_file('/var/www/html/users.xml');

        // =========================================================
        // ❌ VULNERABLE: สร้าง XPath query ด้วย string concat
        // ผู้โจมตีใส่ ' or '1'='1 ใน username → bypass login!
        // =========================================================
        $query = "//user[username='" . $username . "' and password='" . $password . "']";

        try {
            $nodes = @$xml->xpath($query);
            if ($nodes !== false) {
                $result = $nodes;
            } else {
                $error = 'XPath query failed (invalid syntax from injection?)';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>XPath Injection Lab — VULNERABLE</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
    .header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);padding:16px 32px;display:flex;align-items:center;gap:16px}
    .mb{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700;background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3)}
    .title{font-size:18px;font-weight:700}
    .sw{margin-left:auto;color:#6366f1;text-decoration:none;font-size:13px;font-weight:600}
    .bl{color:#8b949e;text-decoration:none;font-size:13px}
    .container{max-width:900px;margin:0 auto;padding:40px 24px}
    .card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:32px;margin-bottom:24px}
    h2{font-size:20px;font-weight:700;margin-bottom:8px}
    .desc{color:#8b949e;font-size:14px;margin-bottom:24px;line-height:1.6}
    label{display:block;font-size:13px;font-weight:600;color:#8b949e;margin-bottom:6px}
    input[type=text],input[type=password]{width:100%;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:10px 14px;color:#f0f6fc;font-size:14px;font-family:'JetBrains Mono',monospace;outline:none;margin-bottom:14px}
    input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
    button{background:linear-gradient(135deg,#6366f1,#a855f7);border:none;color:#fff;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
    button:hover{opacity:.85}
    .cb{background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:16px;margin:16px 0;overflow-x:auto}
    code{font-family:'JetBrains Mono',monospace;font-size:12px;color:#7dd3fc}
    .hl{color:#f97316}
    .hint{font-size:12px;color:#f59e0b;margin-top:6px}
    .qd{background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:8px;padding:10px 14px;margin:14px 0;font-size:12px;font-family:'JetBrains Mono',monospace;color:#a5b4fc;word-break:break-all}
    table{width:100%;border-collapse:collapse;font-size:13px;margin-top:12px}
    th{text-align:left;padding:8px 12px;background:rgba(99,102,241,.15);color:#a5b4fc;font-weight:600;border-bottom:1px solid rgba(255,255,255,.08)}
    td{padding:8px 12px;border-bottom:1px solid rgba(255,255,255,.04);color:#8b949e}
    .res{margin-top:24px;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .error{color:#f87171;font-size:13px;padding:12px;background:rgba(239,68,68,.1);border-radius:8px;border:1px solid rgba(239,68,68,.2)}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="bl">← Dashboard</a>
  <span class="title">📄 XPath Injection</span>
  <span class="mb">🔴 VULNERABLE</span>
  <a href="/secure.php" class="sw">→ ดูโหมด Secure</a>
</div>

<div class="container">
<div class="card">
  <h2>🔴 Vulnerable: XML Login System</h2>
  <p class="desc">
    โปรแกรม Login นี้เก็บ user ใน XML file และใช้ <strong>XPath query</strong> ตรวจสอบ —
    แต่สร้าง query โดย string concatenation ทำให้ injection ได้ง่าย
  </p>

  <div class="cb">
    <code><span class="hl">// ❌ VULNERABLE CODE (PHP)</span>
$query = "//user[username='" . $username . "' and password='" . $password . "']";
$nodes = $xml->xpath($query);
<span class="hl">// ← username=' or '1'='1 → query กลายเป็น always-true!</span></code>
  </div>

  <form method="POST" action="/vulnerable.php">
    <label>Username:</label>
    <input type="text" name="username" id="xpath-user"
           placeholder="ลองใส่: ' or '1'='1 or ''"
           value="<?= htmlspecialchars($username) ?>" />
    <label>Password:</label>
    <input type="text" name="password" id="xpath-pass"
           placeholder="anything"
           value="<?= htmlspecialchars($password) ?>" />
    <p class="hint">💡 Payloads: <code style="color:#f97316">' or '1'='1</code> | <code style="color:#f97316">admin' or '1'='1</code></p>
    <br>
    <button type="submit" id="login-xpath-btn">🔓 Login (Vulnerable)</button>
  </form>

  <?php if ($query): ?>
    <div class="qd">📋 XPath Query: <strong><?= htmlspecialchars($query) ?></strong></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php elseif ($result !== null): ?>
    <div class="res">
      <h3><?= count($result) ?> user(s) พบ</h3>
      <?php if (count($result) > 0): ?>
        <table>
          <tr><th>Username</th><th>Password</th><th>Email</th><th>Role</th><th>Secret</th></tr>
          <?php foreach ($result as $user): ?>
            <tr>
              <td><?= htmlspecialchars((string)$user->username) ?></td>
              <td><?= htmlspecialchars((string)$user->password) ?></td>
              <td><?= htmlspecialchars((string)$user->email) ?></td>
              <td><?= htmlspecialchars((string)$user->role) ?></td>
              <td style="color:#f97316"><?= htmlspecialchars((string)$user->secret) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p style="color:#8b949e">ไม่พบผู้ใช้</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</div>
</body>
</html>
