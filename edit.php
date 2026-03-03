<?php
include 'koneksi.php';
session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM barang WHERE id = :id");
$stmt->execute([':id' => $id]);
$barang = $stmt->fetch();

if (!$barang) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Data barang tidak ditemukan.'];
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang   = trim($_POST['nama_barang'] ?? '');
    $jumlah        = trim($_POST['jumlah'] ?? '');
    $harga         = trim($_POST['harga'] ?? '');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');

    if (empty($nama_barang))                 $errors[] = 'Nama barang wajib diisi.';
    if (!is_numeric($jumlah) || $jumlah < 0) $errors[] = 'Jumlah harus berupa angka positif.';
    if (!is_numeric($harga)  || $harga < 0)  $errors[] = 'Harga harus berupa angka positif.';
    if (empty($tanggal_masuk))               $errors[] = 'Tanggal masuk wajib diisi.';

    if (empty($errors)) {
        $sql  = "UPDATE barang SET nama_barang=:nama, jumlah=:jumlah, harga=:harga, tanggal_masuk=:tgl WHERE id=:id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nama'   => $nama_barang,
            ':jumlah' => (int) $jumlah,
            ':harga'  => (float) $harga,
            ':tgl'    => $tanggal_masuk,
            ':id'     => $id,
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data barang berhasil diperbarui!'];
        header('Location: index.php');
        exit;
    }

    $barang['nama_barang']   = $nama_barang;
    $barang['jumlah']        = $jumlah;
    $barang['harga']         = $harga;
    $barang['tanggal_masuk'] = $tanggal_masuk;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="header-inner">
        <a href="index.php" class="logo"><div class="logo-icon">📦</div>Inventaris<span>App</span></a>
        <nav>
            <a href="index.php">Daftar Barang</a>
            <a href="tambah.php">+ Tambah</a>
        </nav>
    </div>
</header>
<main>
    <div class="page-title">
        <h1>Edit Data Barang</h1>
        <p>Perbarui informasi barang <strong><?= htmlspecialchars($barang['nama_barang']) ?></strong> (ID: #<?= $id ?>).</p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        Terdapat kesalahan:
        <ul style="margin:6px 0 0 16px">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>Form Edit Barang</h2>
            <span style="font-size:.8rem;color:var(--text-muted)">ID #<?= $id ?></span>
        </div>
        <form method="POST" action="edit.php?id=<?= $id ?>">
            <div class="form-grid">
                <div class="form-group full">
                    <label for="nama_barang">Nama Barang</label>
                    <input type="text" id="nama_barang" name="nama_barang"
                           value="<?= htmlspecialchars($barang['nama_barang']) ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label for="jumlah">Jumlah (Unit)</label>
                    <input type="number" id="jumlah" name="jumlah"
                           value="<?= htmlspecialchars($barang['jumlah']) ?>" min="0" required>
                </div>
                <div class="form-group">
                    <label for="harga">Harga Satuan (Rp)</label>
                    <input type="number" id="harga" name="harga"
                           value="<?= htmlspecialchars($barang['harga']) ?>" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="tanggal_masuk">Tanggal Masuk</label>
                    <input type="date" id="tanggal_masuk" name="tanggal_masuk"
                           value="<?= htmlspecialchars($barang['tanggal_masuk']) ?>" required>
                </div>
            </div>
            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Perbarui Data</button>
            </div>
        </form>
    </div>
</main>
<footer>&copy; <?= date('Y') ?> Inventaris App</footer>
</body>
</html>