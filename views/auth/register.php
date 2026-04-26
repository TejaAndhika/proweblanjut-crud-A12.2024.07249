<?php
// register.php - Halaman Registrasi User Baru

include __DIR__ . '/../../public/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, langsung ke public/index.php
if (!empty($_SESSION['user_id'])) {
    header('Location: ../../public/index.php');
    exit;
}

$errors  = [];
$success = '';
$data    = ['username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['username'] = trim($_POST['username'] ?? '');
    $password         = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validasi
    if (empty($data['username'])) {
        $errors[] = 'Username wajib diisi.';
    } elseif (strlen($data['username']) < 3) {
        $errors[] = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
        $errors[] = 'Username hanya boleh huruf, angka, dan underscore (_).';
    }

    if (empty($password)) {
        $errors[] = 'Password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    // Cek apakah username sudah dipakai
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $data['username']]);
        if ($stmt->fetch()) {
            $errors[] = 'Username "' . htmlspecialchars($data['username']) . '" sudah digunakan. Pilih username lain.';
        }
    }

    // Simpan ke database
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt   = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $stmt->execute([':username' => $data['username'], ':password' => $hashed]);

        $success = 'Akun berhasil dibuat! Silakan login menggunakan akun baru kamu.';
        $data    = ['username' => ''];  // Reset form
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi — Inventaris App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:          #0f1117;
            --bg-card:     #1a1d27;
            --bg-input:    #22253a;
            --border:      #2e3347;
            --accent:      #6c63ff;
            --accent-glow: rgba(108,99,255,0.25);
            --success:     #22c55e;
            --success-glow:rgba(34,197,94,0.15);
            --danger:      #ef4444;
            --text:        #e2e8f0;
            --text-muted:  #8892a4;
            --radius:      14px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background-image:
                radial-gradient(ellipse 70% 50% at 80% 10%, rgba(108,99,255,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 10% 85%, rgba(34,197,94,0.07) 0%, transparent 60%);
        }

        .register-wrap {
            width: 100%;
            max-width: 440px;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-icon {
            width: 60px; height: 60px;
            background: var(--accent);
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 1.75rem;
            margin: 0 auto 1rem;
            box-shadow: 0 0 40px rgba(108,99,255,0.35);
        }
        .brand h1 {
            font-family: 'Space Mono', monospace;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .brand h1 span { color: var(--accent); }
        .brand p { color: var(--text-muted); font-size: 0.875rem; margin-top: 4px; }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert {
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
        }
        .alert-danger {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #f87171;
        }
        .alert-danger ul { margin: 6px 0 0 16px; }
        .alert-success {
            background: var(--success-glow);
            border: 1px solid rgba(34,197,94,0.25);
            color: #4ade80;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 1.1rem;
        }
        label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .input-wrap { position: relative; }
        input[type="text"], input[type="password"] {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 11px 40px 11px 14px;
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        input.valid   { border-color: var(--success); }
        input.invalid { border-color: var(--danger); }
        input::placeholder { color: var(--text-muted); }

        .toggle-pw {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            background: none;
            border: none;
            padding: 0;
            font-size: 1rem;
        }
        .toggle-pw:hover { color: var(--text); }

        .hint-text {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Strength bar */
        .strength-wrap { margin-top: 6px; }
        .strength-bar {
            height: 4px;
            border-radius: 99px;
            background: var(--border);
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            border-radius: 99px;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }
        .strength-label {
            font-size: 0.72rem;
            margin-top: 4px;
            color: var(--text-muted);
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 9px;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: all 0.2s;
        }
        .btn-register:hover {
            background: #7c75ff;
            box-shadow: 0 0 0 6px var(--accent-glow);
            transform: translateY(-1px);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .login-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { text-decoration: underline; }

        footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="register-wrap">
    <div class="brand">
        <div class="brand-icon">📦</div>
        <h1>Inventaris<span>App</span></h1>
        <p>Buat akun baru untuk mengakses aplikasi</p>
    </div>

    <div class="card">
        <div class="card-title">✍️ Form Registrasi</div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
            <div style="text-align:center;margin-top:1rem">
                <a href="login.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:var(--accent);color:#fff;border-radius:9px;text-decoration:none;font-weight:700;font-size:0.9rem;transition:all .2s"
                   onmouseover="this.style.background='#7c75ff'" onmouseout="this.style.background='var(--accent)'">
                    🔐 Pergi ke Halaman Login
                </a>
            </div>
        <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                ⚠️ Terdapat kesalahan:
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="register.php" id="registerForm">

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <input type="text" id="username" name="username"
                               placeholder="Contoh: john_doe"
                               value="<?= htmlspecialchars($data['username']) ?>"
                               autocomplete="username"
                               autofocus required>
                    </div>
                    <span class="hint-text">Huruf, angka, dan underscore (_). Minimal 3 karakter.</span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password"
                               placeholder="Minimal 6 karakter"
                               autocomplete="new-password"
                               oninput="checkStrength(this.value)"
                               required>
                        <button type="button" class="toggle-pw" onclick="togglePw('password', this)">👁️</button>
                    </div>
                    <div class="strength-wrap">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <div class="strength-label" id="strengthLabel">Masukkan password untuk melihat kekuatannya</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <div class="input-wrap">
                        <input type="password" id="confirm_password" name="confirm_password"
                               placeholder="Ulangi password"
                               autocomplete="new-password"
                               oninput="checkMatch()"
                               required>
                        <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)">👁️</button>
                    </div>
                    <span class="hint-text" id="matchHint"></span>
                </div>

                <button type="submit" class="btn-register">🚀 Buat Akun</button>
            </form>

        <?php endif; ?>
    </div>

    <div class="login-link">
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>

    <footer>&copy; <?= date('Y') ?> Inventaris App — PHP &amp; PDO</footer>
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}

function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;

    const levels = [
        { pct: '0%',   color: '',          text: '' },
        { pct: '25%',  color: '#ef4444',   text: '🔴 Sangat Lemah' },
        { pct: '50%',  color: '#f59e0b',   text: '🟡 Lemah' },
        { pct: '70%',  color: '#3b82f6',   text: '🔵 Cukup' },
        { pct: '88%',  color: '#22c55e',   text: '🟢 Kuat' },
        { pct: '100%', color: '#6c63ff',   text: '💜 Sangat Kuat' },
    ];
    const lvl = levels[Math.min(score, 5)];
    fill.style.width      = lvl.pct;
    fill.style.background = lvl.color;
    label.textContent     = lvl.text || 'Masukkan password untuk melihat kekuatannya';
}

function checkMatch() {
    const pw      = document.getElementById('password').value;
    const cpw     = document.getElementById('confirm_password');
    const hint    = document.getElementById('matchHint');
    if (cpw.value === '') {
        hint.textContent = '';
        cpw.classList.remove('valid', 'invalid');
        return;
    }
    if (pw === cpw.value) {
        hint.textContent = '✅ Password cocok';
        hint.style.color = '#4ade80';
        cpw.classList.add('valid');
        cpw.classList.remove('invalid');
    } else {
        hint.textContent = '❌ Password tidak cocok';
        hint.style.color = '#f87171';
        cpw.classList.add('invalid');
        cpw.classList.remove('valid');
    }
}
</script>

</body>
</html>