"""
OWASP A05 — SQL Injection Lab (Python Flask + MySQL)

โหมด Vulnerable : ใช้ string concat ตรงๆ ใน query → SQL Injection ได้ง่าย
โหมด Secure     : ใช้ Parameterized Query → injection ไม่ได้ผล
"""

import os
import time
import mysql.connector
from flask import Flask, request, jsonify, render_template_string

app = Flask(__name__)

# ─── DB connection (retry on startup) ────────────────────────────
def get_db():
    for attempt in range(20):
        try:
            return mysql.connector.connect(
                host=os.getenv("DB_HOST", "mysql"),
                user=os.getenv("DB_USER", "labuser"),
                password=os.getenv("DB_PASSWORD", "labpassword"),
                database=os.getenv("DB_NAME", "labdb"),
            )
        except mysql.connector.Error:
            print(f"[DB] Waiting for MySQL... attempt {attempt + 1}")
            time.sleep(3)
    raise RuntimeError("Cannot connect to MySQL after 20 attempts")


# ─── Shared HTML template ─────────────────────────────────────────
PAGE = """<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SQL Injection Lab — {{ mode }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
    .header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);padding:16px 32px;display:flex;align-items:center;gap:16px}
    .mode-badge{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700;letter-spacing:.5px}
    .vuln-badge{background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3)}
    .sec-badge{background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3)}
    .title{font-size:18px;font-weight:700}
    .switch-link{margin-left:auto;color:#6366f1;text-decoration:none;font-size:13px;font-weight:600}
    .switch-link:hover{text-decoration:underline}
    .back-link{color:#8b949e;text-decoration:none;font-size:13px}
    .back-link:hover{color:#f0f6fc}
    .container{max-width:900px;margin:0 auto;padding:40px 24px}
    .card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:32px;margin-bottom:24px}
    h2{font-size:20px;font-weight:700;margin-bottom:8px}
    .desc{color:#8b949e;font-size:14px;margin-bottom:24px;line-height:1.6}
    label{display:block;font-size:13px;font-weight:600;color:#8b949e;margin-bottom:6px}
    input[type=text]{width:100%;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:10px 14px;color:#f0f6fc;font-size:14px;font-family:'JetBrains Mono',monospace;outline:none;margin-bottom:16px}
    input[type=text]:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
    button{background:linear-gradient(135deg,#6366f1,#a855f7);border:none;color:#fff;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:opacity .2s}
    button:hover{opacity:.85}
    .result{margin-top:24px;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .result h3{font-size:15px;font-weight:700;margin-bottom:12px}
    table{width:100%;border-collapse:collapse;font-size:13px}
    th{text-align:left;padding:8px 12px;background:rgba(99,102,241,.15);color:#a5b4fc;font-weight:600;border-bottom:1px solid rgba(255,255,255,.08)}
    td{padding:8px 12px;border-bottom:1px solid rgba(255,255,255,.04);color:#8b949e}
    .error{color:#f87171;font-size:13px;padding:12px;background:rgba(239,68,68,.1);border-radius:8px;border:1px solid rgba(239,68,68,.2)}
    .code-block{background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:16px;margin:16px 0;overflow-x:auto}
    code{font-family:'JetBrains Mono',monospace;font-size:12px;color:#7dd3fc}
    .highlight{color:#f97316}
    .payload-hint{font-size:12px;color:#f59e0b;margin-top:6px}
    .secure-note{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:12px 16px;margin-top:16px;font-size:13px;color:#86efac}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="back-link">← Dashboard</a>
  <span class="title">🗄 SQL Injection</span>
  <span class="mode-badge {{ 'vuln-badge' if mode == 'VULNERABLE' else 'sec-badge' }}">{{ mode }}</span>
  {% if mode == 'VULNERABLE' %}
    <a href="/secure" class="switch-link">→ ดูโหมด Secure</a>
  {% else %}
    <a href="/vulnerable" class="switch-link">→ ดูโหมด Vulnerable</a>
  {% endif %}
</div>
<div class="container">
  <div class="card">
    <h2>{{ '🔴 Vulnerable: ' if mode == 'VULNERABLE' else '🟢 Secure: ' }}ค้นหาผู้ใช้</h2>
    <p class="desc">
      {% if mode == 'VULNERABLE' %}
        โค้ดนี้สร้าง SQL query โดยใช้ <strong>String Concatenation</strong> โดยตรง — 
        ผู้โจมตีสามารถแทรก SQL code เพื่อ bypass, ดึงข้อมูลลับ หรือทำลายฐานข้อมูลได้
      {% else %}
        โค้ดนี้ใช้ <strong>Parameterized Query</strong> — MySQL จะ escape input อัตโนมัติ
        ทำให้ SQL Injection เป็นไปไม่ได้
      {% endif %}
    </p>

    <!-- Source Code Display -->
    <div class="code-block">
      {% if mode == 'VULNERABLE' %}
        <pre><code><span class="highlight"># ❌ VULNERABLE FUNCTION</span>
def run_query_vulnerable(username: str):
    db = get_db()
    cursor = db.cursor()
    # ❌ อันตราย! ใช้ String Concatenation ต่อสตริง query ตรงๆ โดยไม่ผ่านการกรองข้อมูล
    query = f"SELECT id, username, email, role, password FROM users WHERE username='{username}'"
    cursor.execute(query)
    rows = cursor.fetchall()
    db.close()
    return rows, query</code></pre>
      {% else %}
        <pre><code><span style="color:#86efac"># ✅ SECURE FUNCTION</span>
def run_query_secure(username: str):
    db = get_db()
    cursor = db.cursor()
    # ✅ ปลอดภัยด้วย Parameterized Query (ใช้ placeholder %s ส่งค่าแยกต่างหาก)
    query = "SELECT id, username, email, role, password FROM users WHERE username = %s"
    cursor.execute(query, (username,))
    rows = cursor.fetchall()
    db.close()
    return rows, query</code></pre>
      {% endif %}
    </div>

    <form method="POST">
      <label>ค้นหา username:</label>
      <input type="text" name="username" id="username-input"
             placeholder="{{ \"ลองใส่: ' OR '1'='1\" if mode == 'VULNERABLE' else 'alice' }}"
             value="{{ username or '' }}" />
      <p class="payload-hint">💡 Payloads: <code>' OR '1'='1</code> | <code>' UNION SELECT 1,secret,3,4,5,6 FROM secrets-- </code> | <code>admin'--</code></p>
      <button type="submit" id="search-btn">ค้นหา</button>
    </form>

    {% if result is not none %}
    <div class="result">
      <h3>ผลลัพธ์ ({{ result|length }} records)</h3>
      {% if result %}
        <table>
          <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Password</th></tr>
          {% for row in result %}
            <tr>
              <td>{{ row[0] }}</td>
              <td>{{ row[1] }}</td>
              <td>{{ row[2] }}</td>
              <td>{{ row[3] }}</td>
              <td>{{ row[4] }}</td>
            </tr>
          {% endfor %}
        </table>
      {% else %}
        <p style="color:#8b949e">ไม่พบผู้ใช้</p>
      {% endif %}
    </div>
    {% endif %}

    {% if error %}
      <div class="error">⚠️ {{ error }}</div>
    {% endif %}

    {% if mode == 'SECURE' %}
    <div class="secure-note">
      🛡 <strong>การป้องกัน:</strong> Parameterized Query ทำให้ input ถูก treat เป็น data เท่านั้น — 
      ไม่สามารถเป็น SQL code ได้ ไม่ว่าจะใส่อะไรก็ตาม
    </div>
    {% endif %}
  </div>
</div>
</body>
</html>"""


def run_query_vulnerable(username: str):
    """❌ VULNERABLE: String concatenation — SQL Injection possible"""
    db = get_db()
    cursor = db.cursor()
    # อันตราย! ใส่ user input ตรงๆ
    query = f"SELECT id, username, email, role, password FROM users WHERE username='{username}'"
    cursor.execute(query)
    rows = cursor.fetchall()
    db.close()
    return rows, query


def run_query_secure(username: str):
    """✅ SECURE: Parameterized Query — SQL Injection impossible"""
    db = get_db()
    cursor = db.cursor()
    query = "SELECT id, username, email, role, password FROM users WHERE username = %s"
    cursor.execute(query, (username,))
    rows = cursor.fetchall()
    db.close()
    return rows, query


# ─── Routes ──────────────────────────────────────────────────────

@app.route("/")
def index():
    return """<html><body style='background:#030712;color:#f0f6fc;font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh'>
    <div style='text-align:center'>
      <h1 style='font-size:32px;margin-bottom:20px'>🗄 SQL Injection Lab</h1>
      <a href='/vulnerable' style='display:inline-block;margin:8px;padding:12px 28px;background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);border-radius:8px;text-decoration:none;font-weight:600'>🔴 Vulnerable Mode</a>
      <a href='/secure' style='display:inline-block;margin:8px;padding:12px 28px;background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);border-radius:8px;text-decoration:none;font-weight:600'>🟢 Secure Mode</a>
    </div></body></html>"""


@app.route("/vulnerable", methods=["GET", "POST"])
def vulnerable():
    result, error, username = None, None, ""
    if request.method == "POST":
        username = request.form.get("username", "")
        try:
            result, _ = run_query_vulnerable(username)
        except Exception as e:
            error = str(e)
    return render_template_string(PAGE, mode="VULNERABLE", result=result, error=error, username=username)


@app.route("/secure", methods=["GET", "POST"])
def secure():
    result, error, username = None, None, ""
    if request.method == "POST":
        username = request.form.get("username", "")
        try:
            result, _ = run_query_secure(username)
        except Exception as e:
            error = str(e)
    return render_template_string(PAGE, mode="SECURE", result=result, error=error, username=username)


@app.route("/health")
def health():
    return jsonify({"status": "ok", "service": "sql-injection"})


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
