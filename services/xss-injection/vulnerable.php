<?php
/*
 * OWASP A03:2021 — XSS (Cross-Site Scripting) Lab (PHP)
 * ==========================================================
 * หน้านี้จำลองช่องโหว่ Reflected XSS และ Stored XSS
 *
 * ❌ VULNERABLE: เรนเดอร์ user input กลับไปยังหน้าเว็บตรงๆ โดยไม่กรอง
 */

$reflected_name = $_GET['name'] ?? '';
$stored_file = 'comments.json';

// Handle Stored XSS Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'clear') {
        if (file_exists($stored_file)) {
            unlink($stored_file);
        }
        header("Location: /vulnerable.php");
        exit;
    }

    $comment_text = $_POST['comment'] ?? '';
    if (!empty(trim($comment_text))) {
        $comments = [];
        if (file_exists($stored_file)) {
            $comments = json_decode(file_get_contents($stored_file), true) ?? [];
        }
        $comments[] = [
            'text' => $comment_text,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        file_put_contents($stored_file, json_encode($comments));
        header("Location: /vulnerable.php");
        exit;
    }
}

// Load Stored Comments
$comments = [];
if (file_exists($stored_file)) {
    $comments = json_decode(file_get_contents($stored_file), true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>XSS Injection Lab — VULNERABLE</title>
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
    input[type=text], textarea{width:100%;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:10px 14px;color:#f0f6fc;font-size:14px;font-family:inherit;outline:none;margin-bottom:16px}
    input[type=text]:focus, textarea:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
    textarea{height:80px;resize:vertical}
    button, input[type=submit]{background:linear-gradient(135deg,#6366f1,#a855f7);border:none;color:#fff;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.2s}
    button:hover, input[type=submit]:hover{opacity:.85}
    .btn-clear{background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.3);padding:6px 12px;font-size:12px;margin-left:auto}
    .btn-clear:hover{background:rgba(239,68,68,0.2)}
    .code-block{background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:16px;margin:16px 0;overflow-x:auto}
    code{font-family:'JetBrains Mono',monospace;font-size:12px;color:#7dd3fc}
    .highlight{color:#f97316}
    .payload-hint{font-size:12px;color:#f59e0b;margin-top:6px}
    .result-block{margin-top:24px;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .result-block h3{font-size:15px;font-weight:700;margin-bottom:12px;color:#a78bfa}
    .output-xss{padding:14px;background:rgba(255,255,255,0.02);border-radius:8px;border:1px solid rgba(255,255,255,0.05);margin-bottom:16px}
    .comment-list{display:flex;flex-direction:column;gap:12px;margin-top:16px}
    .comment-item{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:8px;padding:14px}
    .comment-meta{font-size:11px;color:#8b949e;margin-bottom:6px;display:flex;justify-content:space-between}
    .comment-text{font-size:13.5px;color:#f0f6fc;line-height:1.5}
    .flex-header{display:flex;align-items:center;margin-bottom:12px}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="back-link">← Dashboard</a>
  <span class="title">🎨 XSS (Cross-Site Scripting)</span>
  <span class="mode-badge">🔴 VULNERABLE</span>
  <a href="/secure.php" class="switch-link">→ ดูโหมด Secure</a>
</div>

<div class="container">
  <!-- Reflected XSS Card -->
  <div class="card">
    <h2>🔴 Vulnerable: Reflected XSS</h2>
    <p class="desc">
      แอปพลิเคชันรับค่าพารามิเตอร์ `name` ผ่าน URL แล้วนำกลับมาเรนเดอร์ในหน้าจอโดยตรงโดยไม่ได้ล้างค่า (Sanitize/Escape) ทำให้เบราว์เซอร์เรียกใช้สคริปต์ที่เป็นอันตรายได้
    </p>

    <div class="code-block">
      <code><span class="highlight"># ❌ VULNERABLE CODE (PHP)</span>
echo "Hello, " . $_GET['name'] . "!";
<span class="highlight">// ↑ อันตราย! พ่นค่าดิบออกจอเบราว์เซอร์ตรงๆ</span></code>
    </div>

    <form method="GET" action="/vulnerable.php">
      <label>กรอกชื่อของคุณ:</label>
      <input type="text" name="name" id="name-input"
             placeholder="ลองกรอก: <script>alert(1)</script>"
             value="<?= htmlspecialchars($reflected_name) ?>" />
      <p class="payload-hint">
        💡 Payloads:
        <code style="color:#f97316">&lt;script&gt;alert('Reflected XSS')&lt;/script&gt;</code> |
        <code style="color:#f97316">&lt;img src=x onerror=alert(1)&gt;</code>
      </p>
      <br>
      <button type="submit" id="reflected-btn">👋 ส่งชื่อ</button>
    </form>

    <?php if ($reflected_name !== ''): ?>
    <div class="result-block">
      <h3>📟 ผลลัพธ์จากการตอบกลับ (Reflected Output):</h3>
      <div class="output-xss">
        สวัสดีคุณ: <?= $reflected_name ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Stored XSS Card -->
  <div class="card">
    <div class="flex-header">
      <h2>🔴 Vulnerable: Stored XSS</h2>
      <?php if (!empty($comments)): ?>
      <form method="POST" action="/vulnerable.php" style="margin-left: auto;">
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="btn-clear" id="clear-btn">🗑️ ล้างข้อความทั้งหมด</button>
      </form>
      <?php endif; ?>
    </div>
    <p class="desc">
      ระบบกล่องข้อความที่บันทึกข้อมูลความคิดเห็นลงไฟล์ฐานข้อมูลจำลองโดยตรงโดยไม่ตรวจกรอง ทำให้สคริปต์ที่โจมตีจะถูกประมวลผลทุกครั้งที่มีผู้อื่นเข้ามาดูหน้านี้
    </p>

    <div class="code-block">
      <code><span class="highlight"># ❌ VULNERABLE CODE (PHP)</span>
foreach ($comments as $comment) {
    echo "&lt;div&gt;" . $comment['text'] . "&lt;/div&gt;";
}
<span class="highlight">// ↑ อันตราย! สคริปต์ที่บันทึกไว้จะทำงานทุกครั้งที่คนมาเปิดหน้าเว็บ</span></code>
    </div>

    <form method="POST" action="/vulnerable.php">
      <label>แสดงความคิดเห็นของคุณ:</label>
      <textarea name="comment" id="comment-input" placeholder="พิมพ์ข้อความของคุณที่นี่..."></textarea>
      <p class="payload-hint">
        💡 Payloads:
        <code style="color:#f97316">&lt;script&gt;alert('Stored XSS')&lt;/script&gt;</code> |
        <code style="color:#f97316">&lt;iframe src="javascript:alert(1)" style="display:none"&gt;&lt;/iframe&gt;</code>
      </p>
      <br>
      <button type="submit" id="stored-btn">💬 บันทึกข้อความ</button>
    </form>

    <div class="result-block">
      <h3>📋 ความคิดเห็นจากผู้ใช้งาน (Stored Comments):</h3>
      <?php if (empty($comments)): ?>
        <p style="color:#8b949e; font-size:13px; text-align:center; padding:20px 0;">ยังไม่มีความคิดเห็นร่วมแสดง</p>
      <?php else: ?>
        <div class="comment-list">
          <?php foreach ($comments as $index => $c): ?>
            <div class="comment-item">
              <div class="comment-meta">
                <span>👤 ผู้ใช้ทั่วไป (ID: <?= $index + 1 ?>)</span>
                <span>📅 <?= htmlspecialchars($c['timestamp']) ?></span>
              </div>
              <div class="comment-text">
                <?= $c['text'] ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
