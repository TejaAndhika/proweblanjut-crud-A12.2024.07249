<!-- http://localhost/views/auth/login.php -->

<?php
// login.php - Halaman & Proses Login (dengan fitur Cookies "Ingat Saya")

require_once __DIR__ . '/../../public/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Cek apakah sudah login via SESSION ──────────────────────────
if (!empty($_SESSION['user_id'])) {
    header('Location: ../../public/index.php');
    exit;
}

// ── Cek Cookie "Remember Me" ─────────────────────────────────────
// Jika cookie remember_token ada, verifikasi ke database lalu auto-login
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt  = $conn->prepare("SELECT * FROM users WHERE remember_token = :token LIMIT 1");
    $stmt->execute([':token' => $token]);
    $user  = $stmt->fetch();

    if ($user) {
        // Token valid — pulihkan sesi
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Perbarui masa berlaku cookie (rolling cookie — 30 hari dari sekarang)
        $new_token = bin2hex(random_bytes(32));
        $expire    = time() + (86400 * 30); // 30 hari
        setcookie('remember_token', $new_token, [
            'expires'  => $expire,
            'path'     => '/',
            'httponly' => true,   // Tidak bisa diakses oleh JavaScript
            'samesite' => 'Lax',
        ]);

        // Simpan token baru ke database
        $stmt = $conn->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
        $stmt->execute([':token' => $new_token, ':id' => $user['id']]);

        header('Location: ../../public/index.php');
        exit;
    } else {
        // Token tidak valid / sudah kedaluwarsa — hapus cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username'] ?? '');
    $password    = trim($_POST['password'] ?? '');
    $remember_me = isset($_POST['remember_me']);

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Ambil user dari database
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // ── Login berhasil — simpan ke SESSION ──
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            // ── Fitur "Ingat Saya" — set COOKIE jika dicentang ──
            if ($remember_me) {
                $token  = bin2hex(random_bytes(32)); // Token acak 64 karakter
                $expire = time() + (86400 * 30);     // Berlaku 30 hari

                setcookie('remember_token', $token, [
                    'expires'  => $expire,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);

                // Simpan token ke kolom remember_token di database
                $stmt = $conn->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                $stmt->execute([':token' => $token, ':id' => $user['id']]);
            }

            $redirect = $_SESSION['redirect_after_login'] ?? '../../public/index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Username atau password salah. Coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Inventaris App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:         #0f1117;
            --bg-card:    #1a1d27;
            --bg-input:   #22253a;
            --border:     #2e3347;
            --accent:     #6c63ff;
            --accent-glow:rgba(108,99,255,0.25);
            --danger:     #ef4444;
            --text:       #e2e8f0;
            --text-muted: #8892a4;
            --radius:     14px;
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
                radial-gradient(ellipse 70% 50% at 20% 20%, rgba(108,99,255,0.14) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 80% 80%, rgba(255,101,132,0.08) 0%, transparent 60%);
        }

        .login-wrap {
            width: 100%;
            max-width: 420px;
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

        .alert-danger {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #f87171;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
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
        input[type="text"], input[type="password"] {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 11px 14px;
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
        input::placeholder { color: var(--text-muted); }

        .password-wrap { position: relative; }
        .toggle-pw {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1rem;
            user-select: none;
            background: none;
            border: none;
            padding: 0;
        }
        .toggle-pw:hover { color: var(--text); }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.1rem;
        }
        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--text-muted);
            user-select: none;
        }
        .remember-label input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
            padding: 0;
            border-radius: 4px;
        }
        .remember-label:hover { color: var(--text); }
        .cookie-badge {
            font-size: 0.72rem;
            background: rgba(108,99,255,0.12);
            color: var(--accent);
            border: 1px solid rgba(108,99,255,0.25);
            border-radius: 999px;
            padding: 2px 9px;
            font-weight: 600;
        }

        .btn-login {
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
            letter-spacing: 0.02em;
        }
        .btn-login:hover {
            background: #7c75ff;
            box-shadow: 0 0 0 6px var(--accent-glow);
            transform: translateY(-1px);
        }
        .btn-login:active { transform: translateY(0); }

        .hint {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 14px;
        }
        .hint strong { color: var(--text); font-family: 'Space Mono', monospace; }

        footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="brand">
        <div class="brand-icon">📦</div>
        <h1>Inventaris<span>App</span></h1>
        <p>Silakan login untuk melanjutkan</p>
    </div>

    <div class="card">
        <?php if ($error): ?>
        <div class="alert-danger">
            <span>⚠️</span> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/public/index.php?page=login">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       placeholder="Masukkan username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       autocomplete="username" autofocus required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password"
                           placeholder="Masukkan password"
                           autocomplete="current-password" required>
                    <button type="button" class="toggle-pw" onclick="togglePassword()" title="Tampilkan password">👁️</button>
                </div>
            </div>

            <div class="remember-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    Ingat Saya (30 hari)
                </label>
                <span class="cookie-badge">🍪 Cookie</span>
            </div>

            <button type="submit" class="btn-login">🔐 Masuk ke Aplikasi</button>
        </form>
    </div>

    <div class="hint">
        Akun default pengujian:<br>
        Username: <strong>admin</strong> &nbsp;|&nbsp; Password: <strong>admin123</strong>
    </div>

    <div style="text-align:center;margin-top:1.25rem;font-size:0.85rem;color:var(--text-muted)">
        Belum punya akun?
       <a href="/public/index.php?page=register"color:var(--accent);text-decoration:none;font-weight:600;margin-left:4px">Daftar di sini →</a>
    </div>

    <footer>&copy; <?= date('Y') ?> Inventaris App — PHP &amp; PDO</footer>
</div>

<script>
function togglePassword() {
    const pw  = document.getElementById('password');
    const btn = document.querySelector('.toggle-pw');
    if (pw.type === 'password') {
        pw.type = 'text';
        btn.textContent = '🙈';
    } else {
        pw.type = 'password';
        btn.textContent = '👁️';
    }
}
</script>

</body>
</html>