<?php
// public/index.php - Router Utama

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'login';

// Halaman yang butuh login
$protected = ['barang', 'tambah', 'edit', 'hapus'];

if (in_array($page, $protected) && empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'index.php?page=' . $page;
    header('Location: index.php?page=login');
    exit;
}

switch ($page) {
    case 'barang':
        require_once __DIR__ . '/../controllers/BarangController.php';
        break;
    case 'tambah':
        require_once __DIR__ . '/../controllers/TambahController.php';
        break;
    case 'edit':
        require_once __DIR__ . '/../controllers/EditController.php';
        break;
    case 'hapus':
        require_once __DIR__ . '/../controllers/HapusController.php';
        break;
    case 'login':
        require_once __DIR__ . '/../controllers/AuthController.php';
        break;
    case 'register':
        require_once __DIR__ . '/../controllers/RegisterController.php';
        break;
    case 'logout':
        require_once __DIR__ . '/../controllers/LogoutController.php';
        break;
    default:
        require_once __DIR__ . '/../controllers/AuthController.php';
        break;
}
?>