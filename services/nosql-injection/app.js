/**
 * OWASP A05 — NoSQL Injection Lab (Node.js + Express + MongoDB)
 * ==============================================================
 * จำลอง Login API ที่:
 *   ❌ /vulnerable/login — รับ JSON body ตรงๆ เข้า MongoDB query
 *   ✅ /secure/login     — ตรวจสอบ type + mongo-sanitize ก่อน query
 */

const express   = require('express');
const mongoose  = require('mongoose');
const sanitize  = require('mongo-sanitize');

const app  = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ─── MongoDB connection (retry) ────────────────────────────────
const MONGO_URL = process.env.MONGO_URL || 'mongodb://admin:adminpassword@mongodb:27017/labdb?authSource=admin';

async function connectDB(retries = 20) {
  for (let i = 0; i < retries; i++) {
    try {
      await mongoose.connect(MONGO_URL);
      console.log('[DB] MongoDB connected');

      // Seed test users
      const existing = await User.findOne({ username: 'admin' });
      if (!existing) {
        await User.insertMany([
          { username: 'admin',   password: 'supersecret123!', role: 'admin' },
          { username: 'alice',   password: 'alice_pass',      role: 'user'  },
          { username: 'bob',     password: 'bob_pass',        role: 'user'  },
          { username: 'charlie', password: 'charlie_pass',    role: 'user'  },
        ]);
        console.log('[DB] Seeded test users');
      }
      return;
    } catch (err) {
      console.log(`[DB] Waiting for MongoDB... attempt ${i + 1}`);
      await new Promise(r => setTimeout(r, 3000));
    }
  }
  throw new Error('Cannot connect to MongoDB');
}

// ─── User Model ───────────────────────────────────────────────
const userSchema = new mongoose.Schema({
  username: String,
  password: String,
  role:     String,
});
const User = mongoose.model('User', userSchema);

// ─── Shared HTML helper ───────────────────────────────────────
const page = (mode, body) => `<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>NoSQL Injection Lab — ${mode}</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#030712;color:#f0f6fc;min-height:100vh}
    .header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);padding:16px 32px;display:flex;align-items:center;gap:16px}
    .vuln{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700;background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3)}
    .sec{padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700;background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3)}
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
    .res{margin-top:24px;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:20px}
    .sn{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:14px;margin-top:16px;font-size:13px;color:#86efac;line-height:1.6}
    .blocked{color:#f87171;font-size:13px;padding:14px;background:rgba(239,68,68,.1);border-radius:8px;border:1px solid rgba(239,68,68,.2)}
    pre{font-family:'JetBrains Mono',monospace;font-size:12px;color:#86efac;white-space:pre-wrap}
    .json-hint{background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:8px;padding:12px;margin-bottom:14px;font-size:12px;color:#a5b4fc}
  </style>
  <link rel="stylesheet" href="http://localhost:8080/service-theme.css">
  <script src="http://localhost:8080/service-theme.js"></script>
</head>
<body>
<div class="header">
  <a href="http://localhost:8080" class="bl">← Dashboard</a>
  <span class="title">🍃 NoSQL Injection</span>
  <span class="${mode === 'VULNERABLE' ? 'vuln' : 'sec'}">${mode === 'VULNERABLE' ? '🔴' : '🟢'} ${mode}</span>
  <a href="${mode === 'VULNERABLE' ? '/secure' : '/vulnerable'}" class="sw">→ ดูโหมด ${mode === 'VULNERABLE' ? 'Secure' : 'Vulnerable'}</a>
</div>
<div class="container"><div class="card">${body}</div></div>
</body></html>`;

// ─── Vulnerable Route ─────────────────────────────────────────
app.get('/vulnerable', (req, res) => {
  res.send(page('VULNERABLE', `
    <h2>🔴 Vulnerable: MongoDB Login</h2>
    <p class="desc">
      โค้ดนี้นำ JSON body ที่ได้จาก request ไปใส่ใน <strong>MongoDB query โดยตรง</strong> —
      ผู้โจมตีสามารถส่ง MongoDB operator เช่น <code style="color:#f97316">$gt</code>, <code style="color:#f97316">$ne</code>
      เพื่อ bypass authentication ได้โดยไม่ต้องรู้ password
    </p>
    <div class="cb">
      <code><span class="hl">// ❌ VULNERABLE CODE (Node.js)</span>
const { username, password } = req.body;
// ← ใช้ req.body โดยตรง ไม่มี validation!
const user = await User.findOne({ username, password });</code>
    </div>
    <div class="json-hint">
      💡 ส่ง JSON body ผ่าน curl หรือ Postman:<br>
      <code>{"username": "admin", "password": {"$gt": ""}}</code><br>
      <code>{"username": {"$ne": null}, "password": {"$ne": null}}</code>
    </div>
    <form method="POST" action="/vulnerable/login" id="vuln-form">
      <label>Username:</label>
      <input type="text" name="username" id="username-vuln" placeholder="admin" />
      <label>Password:</label>
      <input type="text" name="password" id="password-vuln" placeholder='{"$gt": ""}' />
      <p class="hint">💡 Payloads: <code style="color:#f97316">{"$gt": ""}</code> | <code style="color:#f97316">{"$ne": null}</code> | <code style="color:#f97316">{"$regex": ".*"}</code></p>
      <br><button type="submit" id="login-vuln-btn">🔓 Login (Vulnerable)</button>
    </form>
  `));
});

app.post('/vulnerable/login', async (req, res) => {
  try {
    let { username, password } = req.body;

    // ❌ VULNERABLE: Parse JSON payload in password field
    try { password = JSON.parse(password); } catch(e) {}
    try { username = JSON.parse(username); } catch(e) {}

    // ❌ ใส่ user input ลง query โดยตรง — NoSQL operator injection possible!
    const user = await User.findOne({ username, password });

    if (user) {
      res.send(page('VULNERABLE', `
        <h2>🔴 Vulnerable: ผลลัพธ์</h2>
        <div class="res">
          <pre style="color:#f97316">⚠️ Login สำเร็จ! (อาจเป็น Injection)

User ที่พบ:
  username : ${user.username}
  role     : ${user.role}
  password : ${user.password}

💀 ผู้โจมตี bypass authentication ได้โดยไม่รู้ password จริง!</pre>
        </div>
        <br><a href="/vulnerable" style="color:#6366f1;font-size:13px;font-weight:600">← ลองใหม่</a>
      `));
    } else {
      res.send(page('VULNERABLE', `
        <h2>🔴 Vulnerable: ผลลัพธ์</h2>
        <div class="res"><pre style="color:#8b949e">❌ Login ไม่สำเร็จ — ไม่พบผู้ใช้</pre></div>
        <br><a href="/vulnerable" style="color:#6366f1;font-size:13px;font-weight:600">← ลองใหม่</a>
      `));
    }
  } catch (err) {
    res.send(page('VULNERABLE', `<div class="blocked">Error: ${err.message}</div>`));
  }
});

// ─── Secure Route ─────────────────────────────────────────────
app.get('/secure', (req, res) => {
  res.send(page('SECURE', `
    <h2>🟢 Secure: MongoDB Login (ป้องกันแล้ว)</h2>
    <p class="desc">
      โค้ดนี้ใช้ <strong>Type Checking</strong> และ <strong>mongo-sanitize</strong> —
      ทำให้ MongoDB operator ใดๆ ใน input ถูก strip ออกก่อนเข้า query
    </p>
    <div class="cb">
      <code><span style="color:#86efac">// ✅ SECURE CODE (Node.js)</span>

<span style="color:#86efac">// Step 1: Type checking — ยอมรับ string เท่านั้น</span>
if (typeof username !== 'string' || typeof password !== 'string') {
  return res.status(400).json({ error: 'Invalid input type' });
}

<span style="color:#86efac">// Step 2: mongo-sanitize — ลบ $ operators ออกทั้งหมด</span>
const clean = sanitize({ username, password });
const user = await User.findOne(clean);</code>
    </div>
    <form method="POST" action="/secure/login" id="sec-form">
      <label>Username:</label>
      <input type="text" name="username" id="username-sec" placeholder='ลองใส่ {"$gt": ""}' />
      <label>Password:</label>
      <input type="text" name="password" id="password-sec" placeholder='ลองใส่ {"$ne": null}' />
      <br><button type="submit" id="login-sec-btn">🔒 Login (Secure)</button>
    </form>
  `));
});

app.post('/secure/login', async (req, res) => {
  try {
    let { username, password } = req.body;

    // ✅ SECURE Step 1: Type check — ปฏิเสธ object ทันที
    if (typeof username !== 'string' || typeof password !== 'string') {
      return res.send(page('SECURE', `
        <h2>🟢 Secure: ถูก Block!</h2>
        <div class="blocked">❌ Invalid input type — ยอมรับเฉพาะ string เท่านั้น<br>MongoDB operators ถูก reject ทันที</div>
        <br><a href="/secure" style="color:#6366f1;font-size:13px;font-weight:600">← ลองใหม่</a>
      `));
    }

    // ✅ SECURE Step 2: mongo-sanitize strips all $ prefixed keys
    const clean = sanitize({ username, password });
    const user  = await User.findOne(clean);

    if (user) {
      res.send(page('SECURE', `
        <h2>🟢 Secure: Login สำเร็จ</h2>
        <div class="res"><pre style="color:#86efac">✅ Login สำเร็จด้วย credential ถูกต้อง

User: ${user.username} (role: ${user.role})</pre></div>
        <div class="sn">🛡 Input ผ่าน type check และ sanitize แล้ว — operator injection ไม่ได้ผล</div>
        <br><a href="/secure" style="color:#6366f1;font-size:13px;font-weight:600">← ลองใหม่</a>
      `));
    } else {
      res.send(page('SECURE', `
        <h2>🟢 Secure: ผลลัพธ์</h2>
        <div class="res"><pre style="color:#8b949e">❌ Login ไม่สำเร็จ — credential ไม่ถูกต้อง</pre></div>
        <div class="sn">🛡 แม้จะใส่ operator payload ก็ถูก sanitize ออกไปแล้ว</div>
        <br><a href="/secure" style="color:#6366f1;font-size:13px;font-weight:600">← ลองใหม่</a>
      `));
    }
  } catch (err) {
    res.send(page('SECURE', `<div class="blocked">Error: ${err.message}</div>`));
  }
});

// ─── Root ─────────────────────────────────────────────────────
app.get('/', (req, res) => {
  res.send(`<html><body style='background:#030712;color:#f0f6fc;font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh'>
    <div style='text-align:center'>
      <h1 style='font-size:32px;margin-bottom:20px'>🍃 NoSQL Injection Lab</h1>
      <a href='/vulnerable' style='display:inline-block;margin:8px;padding:12px 28px;background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);border-radius:8px;text-decoration:none;font-weight:600'>🔴 Vulnerable</a>
      <a href='/secure' style='display:inline-block;margin:8px;padding:12px 28px;background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);border-radius:8px;text-decoration:none;font-weight:600'>🟢 Secure</a>
    </div></body></html>`);
});

app.get('/health', (req, res) => res.json({ status: 'ok', service: 'nosql-injection' }));

// ─── Start ────────────────────────────────────────────────────
connectDB().then(() => {
  app.listen(3000, () => console.log('[Server] NoSQL Injection Lab running on port 3000'));
});
