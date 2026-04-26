<?php
// logout.php - Proses Logout: Hancurkan sesi, hapus cookie, redirect ke login

include 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Hapus Cookie "Remember Me" ───────────────────────────────────
// Jika cookie remember_token ada, hapus dari database dan dari browser 
if (!empty($_COOKIE['remember_token'])) {
    // Hapus token dari kolom remember_token di tabel users
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = :token");
    $stmt->execute([':token' => $_COOKIE['remember_token']]);

    // Hapus cookie dari browser dengan mengeset waktu kedaluwarsa ke masa lalu
    setcookie('remember_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// ── Hapus semua variabel SESSION ─────────────────────────────────
$_SESSION = [];

// ── Hapus cookie SESSION dari browser ────────────────────────────
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ── Hancurkan SESSION sepenuhnya ──────────────────────────────────
session_destroy();

// ── Redirect ke halaman login ─────────────────────────────────────
header('Location: login.php');
exit;