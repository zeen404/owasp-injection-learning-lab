"""
OWASP A05 — Server-Side Template Injection (SSTI) Lab
======================================================
Python Flask + Jinja2

❌ /vulnerable — ใช้ render_template_string กับ user input โดยตรง
   → Jinja2 evaluate {{ }} expressions → RCE possible!

✅ /secure — ใช้ template file + escape user input
   → User input ไม่ถูก evaluate เป็น template expression
"""

import os
from flask import Flask, request, render_template_string
from markupsafe import escape

app = Flask(__name__)
app.secret_key = "super-secret-key-do-not-expose!"

# ─── Shared styles ────────────────────────────────────────────
STYLE = """<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
.header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);padding:16px 32px;display:flex;align-items:center;gap:16px}
.mb{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700}
.vb{background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3)}
.sb{background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3)}
.title{font-size:18px;font-weight:700}
.sw{margin-left:auto;color:#6366f1;text-decoration:none;font-size:13px;font-weight:600}
.bl{color:#8b949e;text-decoration:none;font-size:13px}
.container{max-width:900px;margin:0 auto;padding:40px 24px}
.card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:32px;margin-bottom:24px}
h2{font-size:20px;font-weight:700;margin-bottom:8px}
.desc{color:#8b949e;font-size:14px;margin-bottom:24px;line-height:1.6}
label{display:block;font-size:13px;font-weight:600;color:#8b949e;margin-bottom:6px}
input[type=text]{width:100%;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:10px 14px;color:#f0f6fc;font-size:14px;font-family:'JetBrains Mono',monospace;outline:none;margin-bottom:16px}
input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
button{background:linear-gradient(135deg,#6366f1,#a855f7);border:none;color:#fff;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
button:hover{opacity:.85}
.cb{background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:16px;margin:16px 0;overflow-x:auto}
code{font-family:'JetBrains Mono',monospace;font-size:12px;color:#7dd3fc}
.hl{color:#f97316}
.hint{font-size:12px;color:#f59e0b;margin-top:6px}
.res{margin-top:24px;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
.res h3{font-size:15px;font-weight:700;margin-bottom:12px}
.res-body{font-family:'JetBrains Mono',monospace;font-size:13px;word-break:break-all;color:#86efac}
.sn{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:14px;margin-top:16px;font-size:13px;color:#86efac;line-height:1.6}
.error{color:#f87171;font-size:13px;padding:12px;background:rgba(239,68,68,.1);border-radius:8px;border:1px solid rgba(239,68,68,.2)}
</style>
<link rel="stylesheet" href="http://localhost:8080/service-theme.css">
<script src="http://localhost:8080/service-theme.js"></script>
"""

# ─── Vulnerable Route ─────────────────────────────────────────
VULN_PAGE = STYLE + """
<div class="header">
  <a href="http://localhost:8080" class="bl">← Dashboard</a>
  <span class="title">🧩 Template Injection (SSTI)</span>
  <span class="mb vb">🔴 VULNERABLE</span>
  <a href="/secure" class="sw">→ ดูโหมด Secure</a>
</div>
<div class="container"><div class="card">
  <h2>🔴 Vulnerable: Greeting Card Generator</h2>
  <p class="desc">
    โค้ดนี้ใช้ <strong>render_template_string()</strong> กับ user input โดยตรง —
    Jinja2 จะ <em>evaluate</em> ทุก expression ใน <code style="color:#f97316">{{ }}</code>
    ทำให้ผู้โจมตีรัน Python code บนเซิร์ฟเวอร์ได้ (RCE)
  </p>
  <div class="cb">
    <pre><code><span class="hl"># ❌ VULNERABLE ROUTE FUNCTION (Flask)</span>
@app.route("/vulnerable", methods=["GET", "POST"])
def vulnerable():
    rendered, error, name = None, None, ""
    if request.method == "POST":
        name = request.form.get("name", "")
        if name:
            try:
                # ❌ สลัก user input (name) ลงใน template string ตรงๆ ผ่าน f-string
                template_str = f"Hello {name}! ยินดีต้อนรับสู่ SSTI Lab 🧩"
                rendered = render_template_string(template_str)
            except Exception as e:
                error = f"Template Error: {str(e)}"
    return render_template_string(VULN_PAGE, ...)</code></pre>
  </div>
  <form method="POST">
    <label>ชื่อของคุณ:</label>
    <input type="text" name="name" id="ssti-name"
           placeholder="ลองใส่: {{7*7}} หรือ {{config.items()}}"
           value="{{ name or '' }}" />
    <p class="hint">💡 Payloads:
      <code style="color:#f97316">{{7*7}}</code> |
      <code style="color:#f97316">{{config.items()}}</code> |
      <code style="color:#f97316">{{request.application.__globals__}}</code>
    </p>
    <button type="submit" id="ssti-vuln-btn">✉️ สร้างการ์ด (Vulnerable)</button>
  </form>
  {% if rendered %}
  <div class="res">
    <h3>📬 ผลลัพธ์จาก Template Engine:</h3>
    <div class="res-body">{{ rendered }}</div>
  </div>
  {% endif %}
  {% if error %}
  <div class="error">{{ error }}</div>
  {% endif %}
</div></div>
"""

# ─── Secure Page Template ─────────────────────────────────────
SECURE_PAGE = STYLE + """
<div class="header">
  <a href="http://localhost:8080" class="bl">← Dashboard</a>
  <span class="title">🧩 Template Injection (SSTI)</span>
  <span class="mb sb">🟢 SECURE</span>
  <a href="/vulnerable" class="sw">→ ดูโหมด Vulnerable</a>
</div>
<div class="container"><div class="card">
  <h2>🟢 Secure: Greeting Card Generator (ป้องกันแล้ว)</h2>
  <p class="desc">
    โค้ดนี้ <strong>escape user input</strong> ด้วย <code>markupsafe.escape()</code> ก่อน
    และใช้ template variable แทน f-string — Jinja2 จะ render input เป็น text ไม่ใช่ expression
  </p>
  <div class="cb">
    <pre><code><span style="color:#86efac"># ✅ SECURE ROUTE FUNCTION (Flask)</span>
@app.route("/secure", methods=["GET", "POST"])
def secure():
    rendered, error, name = None, None, ""
    if request.method == "POST":
        name = request.form.get("name", "")
        if name:
            try:
                # ✅ ปลอดภัยโดยการเรียกใช้ escape() และส่งข้อมูลผ่าน Template context parameter
                safe_name = escape(name)
                rendered = render_template_string(
                    "Hello {{ name }}! ยินดีต้อนรับสู่ SSTI Lab 🧩",
                    name=safe_name
                )
            except Exception as e:
                error = f"Error: {str(e)}"
    return render_template_string(SECURE_PAGE, ...)</code></pre>
  </div>
  <form method="POST">
    <label>ชื่อของคุณ:</label>
    <input type="text" name="name" id="ssti-sec-name"
           placeholder="ลองใส่: {{7*7}} หรือ {{config}}"
           value="{{ name or '' }}" />
    <button type="submit" id="ssti-sec-btn">✉️ สร้างการ์ด (Secure)</button>
  </form>
  {% if rendered %}
  <div class="res">
    <h3>📬 ผลลัพธ์ (Input ถูก escape แล้ว):</h3>
    <div class="res-body">{{ rendered }}</div>
  </div>
  <div class="sn">
    🛡 <strong>markupsafe.escape()</strong> แปลง: <code>{{ }}</code> → <code>&amp;#123;&amp;#123; &amp;#125;&amp;#125;</code><br>
    Template variable <code>{{ name }}</code> ทำให้ Jinja2 render เป็น text เท่านั้น<br>
    ไม่ว่าจะใส่ SSTI payload อะไร ก็จะแสดงเป็น literal string
  </div>
  {% endif %}
  {% if error %}
  <div class="error">{{ error }}</div>
  {% endif %}
</div></div>
"""


@app.route("/")
def index():
    return """<html><body style='background:#030712;color:#f0f6fc;font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh'>
    <div style='text-align:center'>
      <h1 style='font-size:32px;margin-bottom:20px'>🧩 Template Injection (SSTI) Lab</h1>
      <a href='/vulnerable' style='display:inline-block;margin:8px;padding:12px 28px;background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);border-radius:8px;text-decoration:none;font-weight:600'>🔴 Vulnerable</a>
      <a href='/secure' style='display:inline-block;margin:8px;padding:12px 28px;background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);border-radius:8px;text-decoration:none;font-weight:600'>🟢 Secure</a>
    </div></body></html>"""


@app.route("/vulnerable", methods=["GET", "POST"])
def vulnerable():
    rendered, error, name = None, None, ""
    if request.method == "POST":
        name = request.form.get("name", "")
        if name:
            try:
                # ❌ VULNERABLE: f-string inject user input ลง template → RCE!
                template_str = f"Hello {name}! ยินดีต้อนรับสู่ SSTI Lab 🧩"
                rendered = render_template_string(template_str)
            except Exception as e:
                error = f"Template Error: {str(e)}"
    return render_template_string(VULN_PAGE, rendered=rendered, error=error, name=name)


@app.route("/secure", methods=["GET", "POST"])
def secure():
    rendered, error, name = None, None, ""
    if request.method == "POST":
        name = request.form.get("name", "")
        if name:
            try:
                # ✅ SECURE: escape input ก่อน + ส่งเป็น template variable
                safe_name = escape(name)
                rendered = render_template_string(
                    "Hello {{ name }}! ยินดีต้อนรับสู่ SSTI Lab 🧩",
                    name=safe_name
                )
            except Exception as e:
                error = f"Error: {str(e)}"
    return render_template_string(SECURE_PAGE, rendered=rendered, error=error, name=name)


@app.route("/health")
def health():
    from flask import jsonify
    return jsonify({"status": "ok", "service": "template-injection"})


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)
