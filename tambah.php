<?php
include 'koneksi.php';
session_start();

$errors = [];
$data   = ['nama_barang' => '', 'jumlah' => '', 'harga' => '', 'tanggal_masuk' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['nama_barang']   = trim($_POST['nama_barang'] ?? '');
    $data['jumlah']        = trim($_POST['jumlah'] ?? '');
    $data['harga']         = trim($_POST['harga'] ?? '');
    $data['tanggal_masuk'] = trim($_POST['tanggal_masuk'] ?? '');

    if (empty($data['nama_barang'])) $errors[] = 'Nama barang wajib diisi.';
    if (!is_numeric($data['jumlah']) || $data['jumlah'] < 0) $errors[] = 'Jumlah harus berupa angka positif.';
    if (!is_numeric($data['harga'])  || $data['harga'] < 0)  $errors[] = 'Harga harus berupa angka positif.';
    if (empty($data['tanggal_masuk'])) $errors[] = 'Tanggal masuk wajib diisi.';

    if (empty($errors)) {
        $sql  = "INSERT INTO barang (nama_barang, jumlah, harga, tanggal_masuk) VALUES (:nama, :jumlah, :harga, :tgl)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nama'   => $data['nama_barang'],
            ':jumlah' => (int) $data['jumlah'],
            ':harga'  => (float) $data['harga'],
            ':tgl'    => $data['tanggal_masuk'],
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => '✅ Barang "' . htmlspecialchars($data['nama_barang']) . '" berhasil ditambahkan!'];
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang — Inventaris App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-inner">
        <a href="index.php" class="logo">
            <div class="logo-icon">📦</div>
            Inventaris<span>App</span>
        </a>
        <nav>
            <a href="index.php">Daftar Barang</a>
            <a href="tambah.php" class="active">+ Tambah</a>
        </nav>
    </div>
</header>

<main>
    <div class="page-title">
        <h1>➕ Tambah Barang Baru</h1>
        <p>Isi formulir di bawah untuk menambahkan barang ke inventaris.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            ⚠️ Terdapat kesalahan:
            <ul style="margin:6px 0 0 16px">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>📝 Form Tambah Barang</h2>
        </div>
        <form method="POST" action="tambah.php">
            <div class="form-grid">
                <div class="form-group full">
                    <label for="nama_barang">Nama Barang</label>
                    <input type="text" id="nama_barang" name="nama_barang"
                           placeholder="Contoh: Laptop ASUS VivoBook 15"
                           value="<?= htmlspecialchars($data['nama_barang']) ?>"
                           required autofocus>
                </div>
                <div class="form-group">
                    <label for="jumlah">Jumlah (Unit)</label>
                    <input type="number" id="jumlah" name="jumlah"
                           placeholder="0"
                           value="<?= htmlspecialchars($data['jumlah']) ?>"
                           min="0" required>
                </div>
                <div class="form-group">
                    <label for="harga">Harga Satuan (Rp)</label>
                    <input type="number" id="harga" name="harga"
                           placeholder="0"
                           value="<?= htmlspecialchars($data['harga']) ?>"
                           min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="tanggal_masuk">Tanggal Masuk</label>
                    <input type="date" id="tanggal_masuk" name="tanggal_masuk"
                           value="<?= htmlspecialchars($data['tanggal_masuk']) ?>"
                           required>
                </div>
            </div>
            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">← Batal</a>
                <button type="submit" class="btn btn-primary">✅ Simpan Barang</button>
            </div>
        </form>
    </div>
</main>

<footer>
    &copy; <?= date('Y') ?> Inventaris App — Dibangun dengan PHP &amp; PDO
</footer>
</body>
</html>