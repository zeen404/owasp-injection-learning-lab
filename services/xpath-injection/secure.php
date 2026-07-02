<?php
/*
 * OWASP A05 — XPath Injection Lab (PHP)
 * ✅ SECURE MODE
 *
 * ป้องกันด้วย whitelist regex validation
 */

$result   = null;
$error    = '';
$username = '';
$password = '';
$query    = '';
$blocked  = false;
$block_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // ✅ SECURE: Whitelist validation — ยอมรับ alphanumeric และ _ เท่านั้น
    if (!preg_match('/^[a-zA-Z0-9_]{1,50}$/', $username) ||
        !preg_match('/^[a-zA-Z0-9_!@#$%^&*]{1,100}$/', $password)) {
        $blocked   = true;
        $block_msg = "❌ Input ถูก block!\n\n" .
                     "Username หรือ Password มี special character ที่ไม่อนุญาต\n" .
                     "Whitelist Pattern: ^[a-zA-Z0-9_]{1,50}$\n\n" .
                     "XPath metacharacters: ' \" [ ] / = ถูก reject ทันที";
    } else {
        $xml = simplexml_load_file('/var/www/html/users.xml');
        // ✅ Input ผ่าน validation แล้ว — สร้าง query ได้อย่างปลอดภัย
        $query = "//user[username='" . $username . "' and password='" . $password . "']";
        $nodes = @$xml->xpath($query);
        $result = ($nodes !== false) ? $nodes : [];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>XPath Injection Lab — SECURE</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
    .header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);padding:16px 32px;display:flex;align-items:center;gap:16px}
    .mb{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700;background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3)}
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
    .blocked{color:#f87171;font-size:13px;padding:14px;background:rgba(239,68,68,.1);border-radius:8px;border:1px solid rgba(239,68,68,.3);white-space:pre-wrap;font-family:'JetBrains Mono',monospace}
    .qd{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:10px 14px;margin:14px 0;font-size:12px;font-family:'JetBrains Mono',monospace;color:#86efac;word-break:break-all}
    table{width:100%;border-collapse:collapse;font-size:13px;margin-top:12px}
    th{text-align:left;padding:8px 12px;background:rgba(34,197,94,.1);color:#86efac;font-weight:600;border-bottom:1px solid rgba(255,255,255,.08)}
    td{padding:8px 12px;border-bottom:1px solid rgba(255,255,255,.04);color:#8b949e}
    .res{margin-top:24px;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .sn{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:14px;margin-top:16px;font-size:13px;color:#86efac;line-height:1.6}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="bl">← Dashboard</a>
  <span class="title">📄 XPath Injection</span>
  <span class="mb">🟢 SECURE</span>
  <a href="/vulnerable.php" class="sw">→ ดูโหมด Vulnerable</a>
</div>

<div class="container">
<div class="card">
  <h2>🟢 Secure: XML Login System (ป้องกันแล้ว)</h2>
  <p class="desc">
    โค้ดนี้ใช้ <strong>Whitelist Regex Validation</strong> ก่อน XPath query —
    ยอมรับเฉพาะ alphanumeric และ underscore เท่านั้น ตัด XPath metacharacter ทั้งหมด
  </p>

  <div class="cb">
    <pre><code><span style="color:#86efac">// ✅ SECURE REQUEST HANDLER (PHP)</span>
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // ✅ ป้องกันโดยใช้ Whitelist regex validation ตรวจสอบอักขระ
    if (!preg_match('/^[a-zA-Z0-9_]{1,50}$/', $username) ||
        !preg_match('/^[a-zA-Z0-9_!@#$%^&*]{1,100}$/', $password)) {
        $blocked   = true;
        $block_msg = "❌ Input ถูก block! Username หรือ Password มีอักขระพิเศษที่ไม่อนุญาต";
    } else {
        $xml = simplexml_load_file('/var/www/html/users.xml');
        // ✅ ปลอดภัยเนื่องจากข้อมูลที่กรองแล้วเท่านั้นที่สามารถนำมาสร้าง Query ได้
        $query = "//user[username='" . $username . "' and password='" . $password . "']";
        $nodes = @$xml->xpath($query);
        $result = ($nodes !== false) ? $nodes : [];
    }
}</code></pre>
  </div>

  <form method="POST" action="/secure.php">
    <label>Username:</label>
    <input type="text" name="username" id="xpath-sec-user"
           placeholder="ลองใส่ payload: ' or '1'='1" />
    <label>Password:</label>
    <input type="text" name="password" id="xpath-sec-pass"
           placeholder="anything" />
    <br>
    <button type="submit" id="login-xpath-sec-btn">🔒 Login (Secure)</button>
  </form>

  <?php if ($blocked): ?>
    <div style="margin-top:20px">
      <h3 style="font-size:15px;font-weight:700;margin-bottom:12px">🛡 ถูก Block!</h3>
      <div class="blocked"><?= htmlspecialchars($block_msg) ?></div>
    </div>
  <?php elseif ($query): ?>
    <div class="qd">📋 XPath Query: <strong><?= htmlspecialchars($query) ?></strong></div>
    <div class="res">
      <h3><?= count($result ?? []) ?> user(s) พบ</h3>
      <?php if (!empty($result)): ?>
        <table>
          <tr><th>Username</th><th>Email</th><th>Role</th></tr>
          <?php foreach ($result as $user): ?>
            <tr>
              <td><?= htmlspecialchars((string)$user->username) ?></td>
              <td><?= htmlspecialchars((string)$user->email) ?></td>
              <td><?= htmlspecialchars((string)$user->role) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p style="color:#8b949e">ไม่พบผู้ใช้ — credential ไม่ถูกต้อง</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="sn">
    🛡 <strong>Whitelist Validation</strong> ปฏิเสธทุก character ที่ไม่อยู่ใน list:<br>
    อนุญาต: <code>a-z A-Z 0-9 _</code><br>
    Blocked: <code>' " [ ] / \ ( ) = | &lt; &gt; ; ...</code><br>
    XPath metacharacter ทั้งหมดถูก reject ก่อนถึง query
  </div>
</div>
</div>
</body>
</html>
