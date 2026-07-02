<?php
/*
 * OWASP A03:2021 — XSS (Cross-Site Scripting) Lab (PHP)
 * ==========================================================
 * หน้านี้จำลองการป้องกันช่องโหว่ Reflected XSS และ Stored XSS
 *
 * 🟢 SECURE: แปลงอินพุตของยูสเซอร์ด้วย htmlspecialchars() ก่อนพ่นออกหน้าจอ
 */

$reflected_name = $_GET['name'] ?? '';
$stored_file = 'comments.json';

// Handle Stored XSS Post (Secure)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'clear') {
        if (file_exists($stored_file)) {
            unlink($stored_file);
        }
        header("Location: /secure.php");
        exit;
    }

    $comment_text = $_POST['comment'] ?? '';
    if (!empty(trim($comment_text))) {
        $comments = [];
        if (file_exists($stored_file)) {
            $comments = json_decode(file_get_contents($stored_file), true) ?? [];
        }
        
        // บันทึกตรงๆ ได้ (ข้อมูลดิบ) แต่สิ่งสำคัญคือต้อง Escape ตอนแสดงผล
        // หรือจะกรองตอนขาเข้าด้วยก็ได้ แต่การทำ Output Encoding ตอนแสดงผลคือการป้องกันที่สำคัญที่สุด
        $comments[] = [
            'text' => $comment_text,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        file_put_contents($stored_file, json_encode($comments));
        header("Location: /secure.php");
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
  <title>XSS Injection Lab — SECURE</title>
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
    input[type=text], textarea{width:100%;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:10px 14px;color:#f0f6fc;font-size:14px;font-family:inherit;outline:none;margin-bottom:16px}
    input[type=text]:focus, textarea:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
    textarea{height:80px;resize:vertical}
    button, input[type=submit]{background:linear-gradient(135deg,#6366f1,#a855f7);border:none;color:#fff;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.2s}
    button:hover, input[type=submit]:hover{opacity:.85}
    .btn-clear{background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.3);padding:6px 12px;font-size:12px;margin-left:auto}
    .btn-clear:hover{background:rgba(239,68,68,0.2)}
    .code-block{background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:16px;margin:16px 0;overflow-x:auto}
    code{font-family:'JetBrains Mono',monospace;font-size:12px;color:#7dd3fc}
    .highlight{color:#22c55e}
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
  <span class="mode-badge">🟢 SECURE</span>
  <a href="/vulnerable.php" class="switch-link">→ ดูโหมด Vulnerable</a>
</div>

<div class="container">
  <!-- Reflected XSS Card -->
  <div class="card">
    <h2>🟢 Secure: Reflected XSS Prevention</h2>
    <p class="desc">
      แก้ไขโดยใช้ฟังก์ชัน <strong>htmlspecialchars()</strong> เพื่อทำ Output Encoding แปลงอักขระพิเศษของ HTML (เช่น `&lt;` เป็น `&amp;lt;`) ทำให้บราวเซอร์เข้าใจว่าเป็นตัวอักษรธรรมดาและไม่รันคำสั่งสคริปต์
    </p>

    <div class="code-block">
      <code><span class="highlight"># 🟢 SECURE CODE (PHP)</span>
echo "Hello, " . htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8') . "!";
<span class="highlight">// ↑ ปลอดภัย! มีการทำ Output Encoding ก่อนแสดงผลเสมอ</span></code>
    </div>

    <form method="GET" action="/secure.php">
      <label>กรอกชื่อของคุณ:</label>
      <input type="text" name="name" id="name-input"
             placeholder="ลองกรอก: <script>alert(1)</script>"
             value="<?= htmlspecialchars($reflected_name) ?>" />
      <br>
      <button type="submit" id="reflected-btn">👋 ส่งชื่อ</button>
    </form>

    <?php if ($reflected_name !== ''): ?>
    <div class="result-block">
      <h3>📟 ผลลัพธ์ที่แปลงอย่างปลอดภัย (Secure Output):</h3>
      <div class="output-xss">
        สวัสดีคุณ: <?= htmlspecialchars($reflected_name, ENT_QUOTES, 'UTF-8') ?>
      </div>
      <p style="font-size:12px; color:#8b949e; margin-top:8px;">
        💡 สังเกตผลลัพธ์ใน HTML Source: <code><?= htmlspecialchars(htmlspecialchars($reflected_name, ENT_QUOTES, 'UTF-8')) ?></code>
      </p>
    </div>
    <?php endif; ?>
  </div>

  <!-- Stored XSS Card -->
  <div class="card">
    <div class="flex-header">
      <h2>🟢 Secure: Stored XSS Prevention</h2>
      <?php if (!empty($comments)): ?>
      <form method="POST" action="/secure.php" style="margin-left: auto;">
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="btn-clear" id="clear-btn">🗑️ ล้างข้อความทั้งหมด</button>
      </form>
      <?php endif; ?>
    </div>
    <p class="desc">
      ความเห็นที่ดึงขึ้นมาจากฐานข้อมูลจะถูกประมวลผลผ่าน <strong>htmlspecialchars()</strong> ก่อนส่งกลับไปยังเบราว์เซอร์ เพื่อให้แน่ใจว่ามัลแวร์สคริปต์ที่อาจถูกส่งเข้ามาจะไม่สามารถทำงานบนเบราว์เซอร์ของผู้ชมคนอื่นได้
    </p>

    <div class="code-block">
      <code><span class="highlight"># 🟢 SECURE CODE (PHP)</span>
foreach ($comments as $comment) {
    echo "&lt;div&gt;" . htmlspecialchars($comment['text'], ENT_QUOTES, 'UTF-8') . "&lt;/div&gt;";
}
<span class="highlight">// ↑ ปลอดภัย! กรองก่อนแสดงผล ทำให้สคริปต์แปลกปลอมทำงานไม่ได้</span></code>
    </div>

    <form method="POST" action="/secure.php">
      <label>แสดงความคิดเห็นของคุณ:</label>
      <textarea name="comment" id="comment-input" placeholder="พิมพ์ข้อความของคุณที่นี่..."></textarea>
      <br>
      <button type="submit" id="stored-btn">💬 บันทึกข้อความ</button>
    </form>

    <div class="result-block">
      <h3>📋 ความคิดเห็นจากผู้ใช้งาน (Secure Rendered Comments):</h3>
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
                <?= htmlspecialchars($c['text'], ENT_QUOTES, 'UTF-8') ?>
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
