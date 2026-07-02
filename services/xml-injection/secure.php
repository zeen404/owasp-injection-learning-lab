<?php
/*
 * OWASP A05 — XML / XXE Injection Lab (PHP)
 * ✅ SECURE MODE
 *
 * ป้องกันด้วย:
 * 1. ไม่ใช้ LIBXML_NOENT และ LIBXML_DTDLOAD
 * 2. ใช้ libxml_disable_entity_loader(true) [PHP < 8.0]
 *    หรือ ไม่ pass flags ที่อันตราย [PHP 8.0+]
 */

$output  = '';
$error   = '';
$xml_in  = '';
$blocked = false;

$default_xml = '<?xml version="1.0" encoding="UTF-8"?>
<order>
  <item>Widget A</item>
  <quantity>2</quantity>
  <note>Normal order</note>
</order>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $xml_in = $_POST['xml_input'] ?? '';

    if (!empty($xml_in)) {
        // ✅ SECURE: ตรวจจับ DOCTYPE/ENTITY ใน input ก่อน parse
        if (stripos($xml_in, '<!DOCTYPE') !== false ||
            stripos($xml_in, '<!ENTITY') !== false) {
            $blocked = true;
        } else {
            // ✅ SECURE: parse โดยไม่ใช้ LIBXML_NOENT/LIBXML_DTDLOAD
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadXML($xml_in, LIBXML_NOERROR);  // ← no dangerous flags!

            $errors = libxml_get_errors();
            libxml_clear_errors();

            if (!empty($errors)) {
                $error = 'XML Parse Error: ' . $errors[0]->message;
            } else {
                $output = $dom->saveXML();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>XML/XXE Injection Lab — SECURE</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
    .header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);padding:16px 32px;display:flex;align-items:center;gap:16px}
    .mb{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700;background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3)}
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
    .blocked-box{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:20px;margin-top:20px}
    .blocked-box h3{font-size:15px;font-weight:700;margin-bottom:10px;color:#f87171}
    .blocked-msg{font-family:'JetBrains Mono',monospace;font-size:12px;color:#f87171;white-space:pre-wrap}
    .res{margin-top:24px;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .res h3{font-size:15px;font-weight:700;margin-bottom:12px;color:#a78bfa}
    .output{font-family:'JetBrains Mono',monospace;font-size:12px;color:#86efac;white-space:pre-wrap;word-break:break-all}
    .sn{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:14px;margin-top:16px;font-size:13px;color:#86efac;line-height:1.6}
    .payload-tabs{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap}
    .pt{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#f87171;padding:6px 12px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer}
    .pt:hover{background:rgba(239,68,68,.2)}
    .error{color:#f87171;font-size:13px;padding:12px;background:rgba(239,68,68,.1);border-radius:8px}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="bl">← Dashboard</a>
  <span class="title">📦 XML/XXE Injection</span>
  <span class="mb">🟢 SECURE</span>
  <a href="/vulnerable.php" class="sw">→ ดูโหมด Vulnerable</a>
</div>

<div class="container">
<div class="card">
  <h2>🟢 Secure: XML Parser (XXE Disabled)</h2>
  <p class="desc">
    XML parser นี้ <strong>ไม่ใช้ LIBXML_NOENT/LIBXML_DTDLOAD</strong>
    และตรวจจับ <code>&lt;!DOCTYPE&gt;</code> / <code>&lt;!ENTITY&gt;</code> ก่อน parse —
    ทำให้ XXE attack ไม่สามารถทำงานได้
  </p>

  <div class="cb">
    <code><span style="color:#86efac">// ✅ SECURE CODE (PHP)</span>

<span style="color:#86efac">// Step 1: ตรวจจับ DOCTYPE และ ENTITY ใน raw input</span>
if (stripos($xml_in, '&lt;!DOCTYPE') !== false ||
    stripos($xml_in, '&lt;!ENTITY') !== false) {
    die("XXE attack detected — DOCTYPE/ENTITY not allowed");
}

<span style="color:#86efac">// Step 2: parse โดยไม่ใช้ flags อันตราย</span>
$dom = new DOMDocument();
$dom->loadXML($xml_in, LIBXML_NOERROR);  <span style="color:#86efac">// ← safe!</span></code>
  </div>

  <div class="payload-tabs">
    <p style="font-size:12px;color:#f59e0b;margin-bottom:8px">💡 ลองใส่ XXE payload — จะถูก block ทั้งหมด:</p>
    <button class="pt" id="load-xxe" onclick="loadPayload('xxe')">💀 XXE Payload</button>
    <button class="pt" id="load-normal" onclick="loadPayload('normal')">📄 Normal XML</button>
  </div>

  <form method="POST" action="/secure.php" id="sec-xml-form">
    <label>XML Input:</label>
    <textarea name="xml_input" id="sec-xml-input"><?= htmlspecialchars($xml_in ?: $default_xml) ?></textarea>
    <button type="submit" id="parse-sec-btn">🔒 Parse XML (Secure)</button>
  </form>

  <?php if ($blocked): ?>
    <div class="blocked-box">
      <h3>🛡 XXE Attack Detected — Blocked!</h3>
      <div class="blocked-msg">❌ DOCTYPE หรือ ENTITY พบใน XML input

Blocked characters/keywords:
  <!DOCTYPE  ← external DTD definition
  <!ENTITY   ← entity definition (internal/external)

การป้องกัน:
  1. Reject ทุก input ที่มี DOCTYPE/ENTITY
  2. ไม่ใช้ LIBXML_NOENT ใน loadXML()
  3. External entities ถูก disable โดย default ใน PHP 8.0+</div>
    </div>
  <?php elseif (!empty($output)): ?>
    <div class="res">
      <h3>📋 XML Parsed Output (Safe):</h3>
      <pre class="output"><?= htmlspecialchars($output) ?></pre>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="sn">
    🛡 <strong>การป้องกัน 2 ชั้น:</strong><br>
    1. <strong>Input validation</strong>: ปฏิเสธ DOCTYPE/ENTITY ก่อน parse<br>
    2. <strong>Safe flags</strong>: ไม่ใช้ LIBXML_NOENT และ LIBXML_DTDLOAD<br>
    PHP 8.0+ ปิด external entity โดย default แต่ควรป้องกันเพิ่มเติมด้วย
  </div>
</div>
</div>

<script>
const payloads = {
  xxe: `<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE data [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<data>
  <item>Test</item>
  <note>&xxe;</note>
</data>`,
  normal: `<?xml version="1.0" encoding="UTF-8"?>
<order>
  <item>Widget A</item>
  <quantity>2</quantity>
  <note>Normal order — no injection</note>
</order>`
};
function loadPayload(t) {
  document.getElementById('sec-xml-input').value = payloads[t];
}
</script>
</body>
</html>
