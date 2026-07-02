"""
OWASP A05 — LDAP Injection Lab (Python Flask + OpenLDAP)

โหมด Vulnerable : สร้าง LDAP filter โดย string concat → injection ได้
โหมด Secure     : escape special characters ก่อนสร้าง filter
"""

import os
import time
from flask import Flask, request, render_template_string
from ldap3 import Server, Connection, ALL, SUBTREE
from ldap3.utils.conv import escape_filter_chars

app = Flask(__name__)

LDAP_HOST = os.getenv("LDAP_HOST", "openldap")
LDAP_PORT = int(os.getenv("LDAP_PORT", "389"))
BASE_DN   = os.getenv("LDAP_BASE_DN", "dc=lab,dc=local")
BIND_DN   = os.getenv("LDAP_BIND_DN", "cn=readonly,dc=lab,dc=local")
BIND_PW   = os.getenv("LDAP_BIND_PW", "readonlypassword")

# ─── LDAP Connection helper ───────────────────────────────────
def ldap_search(ldap_filter: str):
    """Run LDAP search with given filter, return list of entries"""
    for attempt in range(15):
        try:
            server = Server(LDAP_HOST, port=LDAP_PORT, get_info=ALL)
            conn   = Connection(server, BIND_DN, BIND_PW, auto_bind=True)
            conn.search(
                search_base=BASE_DN,
                search_filter=ldap_filter,
                search_scope=SUBTREE,
                attributes=["uid", "cn", "mail", "ou", "userPassword"],
            )
            entries = []
            for entry in conn.entries:
                entries.append({
                    "uid":      str(entry.uid)          if entry.uid          else "",
                    "cn":       str(entry.cn)           if entry.cn           else "",
                    "mail":     str(entry.mail)         if entry.mail         else "",
                    "ou":       str(entry.ou)           if entry.ou           else "",
                    "password": str(entry.userPassword) if entry.userPassword else "(hidden)",
                })
            conn.unbind()
            return entries, None
        except Exception as e:
            if "Unable to open socket" in str(e) or "Connection refused" in str(e):
                print(f"[LDAP] Waiting for OpenLDAP... attempt {attempt+1}")
                time.sleep(3)
            else:
                return [], str(e)
    return [], "Cannot connect to OpenLDAP"


# ─── Shared HTML template ─────────────────────────────────────
PAGE = '''<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>LDAP Injection Lab — {{ mode }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
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
    .filter-display{background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:8px;padding:10px 14px;margin:14px 0;font-size:12px;font-family:'JetBrains Mono',monospace;color:#a5b4fc}
    table{width:100%;border-collapse:collapse;font-size:13px;margin-top:12px}
    th{text-align:left;padding:8px 12px;background:rgba(99,102,241,.15);color:#a5b4fc;font-weight:600;border-bottom:1px solid rgba(255,255,255,.08)}
    td{padding:8px 12px;border-bottom:1px solid rgba(255,255,255,.04);color:#8b949e}
    .error{color:#f87171;font-size:13px;padding:12px;background:rgba(239,68,68,.1);border-radius:8px}
    .sn{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:14px;margin-top:16px;font-size:13px;color:#86efac;line-height:1.6}
    .res{margin-top:24px;background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .res h3{font-size:15px;font-weight:700;margin-bottom:12px}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="bl">← Dashboard</a>
  <span class="title">📁 LDAP Injection</span>
  <span class="mb {{ 'vb' if mode=='VULNERABLE' else 'sb' }}">{{ '🔴' if mode=='VULNERABLE' else '🟢' }} {{ mode }}</span>
  <a href="{{ '/secure' if mode=='VULNERABLE' else '/vulnerable' }}" class="sw">→ ดูโหมด {{ 'Secure' if mode=='VULNERABLE' else 'Vulnerable' }}</a>
</div>
<div class="container">
<div class="card">
  <h2>{{ '🔴 Vulnerable' if mode=='VULNERABLE' else '🟢 Secure' }}: ค้นหาผู้ใช้ใน LDAP Directory</h2>
  <p class="desc">
    {% if mode == 'VULNERABLE' %}
      โค้ดนี้สร้าง LDAP filter โดย <strong>string concatenation</strong> —
      ผู้โจมตีแทรก metacharacter เช่น <code style="color:#f97316">* ) ( |</code>
      เพื่อดัดแปลง filter และดึงข้อมูลทั้งหมดออกมาได้
    {% else %}
      โค้ดนี้ใช้ <strong>escape_filter_chars()</strong> จาก ldap3 —
      escape metacharacter ทั้งหมดก่อนสร้าง filter
    {% endif %}
  </p>

  <div class="cb">
    {% if mode == 'VULNERABLE' %}
      <pre><code><span class="hl"># ❌ VULNERABLE ROUTE FUNCTION</span>
@app.route("/vulnerable", methods=["GET", "POST"])
def vulnerable():
    result, error, username, filter_used = None, None, "", ""
    if request.method == "POST":
        username = request.form.get("username", "")
        if username:
            # ❌ ป้อนตัวแปร username ลงใน LDAP filter โดยตรง
            filter_used = f"(&(objectClass=inetOrgPerson)(uid={username}))"
            result, error = ldap_search(filter_used)
    return render_template_string(...)</code></pre>
    {% else %}
      <pre><code><span style="color:#86efac"># ✅ SECURE ROUTE FUNCTION</span>
@app.route("/secure", methods=["GET", "POST"])
def secure():
    result, error, username, filter_used = None, None, "", ""
    if request.method == "POST":
        username = request.form.get("username", "")
        if username:
            # ✅ ป้องกันโดยใช้ escape_filter_chars เพื่อกรองอักขระพิเศษ LDAP
            safe_username = escape_filter_chars(username)
            filter_used   = f"(&(objectClass=inetOrgPerson)(uid={safe_username}))"
            result, error = ldap_search(filter_used)
    return render_template_string(...)</code></pre>
    {% endif %}
  </div>

  <form method="POST">
    <label>ค้นหา Username:</label>
    <input type="text" name="username" id="username-ldap"
           placeholder="{{ \"ลองใส่: * หรือ *)(uid=*))(|(uid=*\" if mode=='VULNERABLE' else 'alice' }}"
           value="{{ username or '' }}" />
    <p class="hint">💡 Payloads: <code style="color:#f97316">*</code> (ดูทุกคน) | <code style="color:#f97316">admin)(&)</code> (bypass) | <code style="color:#f97316">*)(uid=*))(|(uid=*</code></p>
    <button type="submit" id="search-ldap-btn">🔍 ค้นหา</button>
  </form>

  {% if filter_used %}
    <div class="filter-display">📋 LDAP Filter ที่ใช้: <strong>{{ filter_used }}</strong></div>
  {% endif %}

  {% if result is not none %}
  <div class="res">
    <h3>ผลลัพธ์ ({{ result|length }} entries)</h3>
    {% if result %}
      <table>
        <tr><th>UID</th><th>CN</th><th>Mail</th><th>OU</th><th>Password</th></tr>
        {% for r in result %}
          <tr>
            <td>{{ r.uid }}</td>
            <td>{{ r.cn }}</td>
            <td>{{ r.mail }}</td>
            <td>{{ r.ou }}</td>
            <td>{{ r.password }}</td>
          </tr>
        {% endfor %}
      </table>
    {% else %}
      <p style="color:#8b949e">ไม่พบผู้ใช้</p>
    {% endif %}
  </div>
  {% endif %}

  {% if error %}<div class="error">⚠️ {{ error }}</div>{% endif %}

  {% if mode == 'SECURE' %}
  <div class="sn">
    🛡 <strong>escape_filter_chars()</strong> แปลง metacharacter เป็น escape sequence:<br>
    <code>* → \2a</code> | <code>( → \28</code> | <code>) → \29</code> | <code>\ → \5c</code> | <code>NUL → \00</code><br>
    ทำให้ payload ทั้งหมดกลายเป็น literal string ไม่ใช่ LDAP operator
  </div>
  {% endif %}
</div>
</div>
</body>
</html>'''


@app.route("/")
def index():
    return """<html><body style='background:#030712;color:#f0f6fc;font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh'>
    <div style='text-align:center'>
      <h1 style='font-size:32px;margin-bottom:20px'>📁 LDAP Injection Lab</h1>
      <a href='/vulnerable' style='display:inline-block;margin:8px;padding:12px 28px;background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);border-radius:8px;text-decoration:none;font-weight:600'>🔴 Vulnerable</a>
      <a href='/secure' style='display:inline-block;margin:8px;padding:12px 28px;background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);border-radius:8px;text-decoration:none;font-weight:600'>🟢 Secure</a>
    </div></body></html>"""


@app.route("/vulnerable", methods=["GET", "POST"])
def vulnerable():
    result, error, username, filter_used = None, None, "", ""
    if request.method == "POST":
        username = request.form.get("username", "")
        if username:
            # ❌ VULNERABLE: ใส่ username ลง filter โดยตรง
            filter_used = f"(&(objectClass=inetOrgPerson)(uid={username}))"
            result, error = ldap_search(filter_used)
    return render_template_string(
        PAGE, mode="VULNERABLE", result=result, error=error,
        username=username, filter_used=filter_used
    )


@app.route("/secure", methods=["GET", "POST"])
def secure():
    result, error, username, filter_used = None, None, "", ""
    if request.method == "POST":
        username = request.form.get("username", "")
        if username:
            # ✅ SECURE: escape ทุก metacharacter ก่อน
            safe_username = escape_filter_chars(username)
            filter_used   = f"(&(objectClass=inetOrgPerson)(uid={safe_username}))"
            result, error = ldap_search(filter_used)
    return render_template_string(
        PAGE, mode="SECURE", result=result, error=error,
        username=username, filter_used=filter_used
    )


@app.route("/health")
def health():
    from flask import jsonify
    return jsonify({"status": "ok", "service": "ldap-injection"})


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
