<!-- http://localhost/index.php -->

<?php

include __DIR__ . '/../../public/koneksi.php';

// ── Manajemen Sesi & Cookie ──────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    if (!empty($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];

        // Prepared Statement — SELECT by token
        $stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            $new_token = bin2hex(random_bytes(32));
            setcookie('remember_token', $new_token, [
                'expires'  => time() + (86400 * 30),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            // Prepared Statement — UPDATE token
            $stmt = $conn->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
            $stmt->execute([':token' => $new_token, ':id' => $user['id']]);
        } else {
            setcookie('remember_token', '', time() - 3600, '/');
            header('Location: login.php');
            exit;
        }
    } else {
        header('Location: login.php');
        exit;
    }
}

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Prepared Statement — SELECT semua barang
$stmt = $conn->query("SELECT * FROM barang ORDER BY id DESC");
$barang_list = $stmt->fetchAll();

// Statistik
$total_item  = count($barang_list);
$total_stok  = array_sum(array_column($barang_list, 'jumlah'));
$total_nilai = array_sum(array_map(fn($b) => $b['jumlah'] * $b['harga'], $barang_list));

function formatRupiah($num) {
    return 'Rp ' . number_format($num, 0, ',', '.');
}
function stockBadge($qty) {
    if ($qty <= 5)  return ['badge-low',    'Kritis'];
    if ($qty <= 20) return ['badge-medium', 'Sedang'];
    return ['badge-ok', 'Aman'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaris Barang — Daftar Barang</title>
    <link rel="stylesheet" href="../../public/style.css">
    <style>
        .barang-img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-input);
        }
        .no-img {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<header>
    <div class="header-inner">
        <a href="../../public/index.php" class="logo">
            <div class="logo-icon">📦</div>
            Inventaris<span>App</span>
        </a>
        <nav>
           <a href="index.php?page=logout">Log Out</a>
            <a href="index.php?page=barang" class="active">Daftar Barang</a>
            <a href="index.php?page=tambah">+ Tambah</a>
        </nav>
    </div>
</header>

<main>
    <div class="page-title">
        <h1>📋 Manajemen Inventaris</h1>
        <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>. Kelola seluruh data barang dengan mudah.</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon purple">📦</div>
            <div>
                <div class="stat-label">Total Jenis Barang</div>
                <div class="stat-value"><?= $total_item ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">🔢</div>
            <div>
                <div class="stat-label">Total Stok</div>
                <div class="stat-value"><?= number_format($total_stok, 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">💰</div>
            <div>
                <div class="stat-label">Total Nilai Inventaris</div>
                <div class="stat-value" style="font-size:0.95rem"><?= formatRupiah($total_nilai) ?></div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-header">
            <h2>Data Barang</h2>
            <a href="/views/barang/tambah.php" class="btn btn-primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Barang
            </a>
        </div>

        <div class="table-wrap">
            <?php if (empty($barang_list)): ?>
                <div class="empty-state">
                    <div class="big-icon">📭</div>
                    <p>Belum ada data barang. Mulai tambahkan sekarang!</p>
                    <a href="/views/barang/tambah.php" class="btn btn-primary">+ Tambah Barang Pertama</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Gambar</th>
                            <th>Nama Barang</th>
                            <th>Jumlah</th>
                            <th>Harga Satuan</th>
                            <th>Total Nilai</th>
                            <th>Tanggal Masuk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($barang_list as $b):
                            [$badgeClass, $badgeLabel] = stockBadge($b['jumlah']);
                        ?>
                        <tr>
                            <td class="mono" style="color:var(--text-muted)"><?= $b['id'] ?></td>
                            <td>
                                <?php if (!empty($b['gambar']) && file_exists(__DIR__ . '/../public/uploads/barang/' . $b['gambar'])): ?>
                                   <img src="../../public/uploads/barang/<?= htmlspecialchars($b['gambar']) ?>" ...>
                                         alt="<?= htmlspecialchars($b['nama_barang']) ?>"
                                         class="barang-img"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="no-img" title="Belum ada gambar">🖼️</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($b['nama_barang']) ?></strong></td>
                            <td>
                                <?= number_format($b['jumlah'], 0, ',', '.') ?>
                                <span class="badge <?= $badgeClass ?>" style="margin-left:6px"><?= $badgeLabel ?></span>
                            </td>
                            <td class="mono"><?= formatRupiah($b['harga']) ?></td>
                            <td class="mono"><?= formatRupiah($b['jumlah'] * $b['harga']) ?></td>
                            <td><?= date('d M Y', strtotime($b['tanggal_masuk'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a href="edit.php?id=<?= $b['id'] ?>" class="btn btn-edit btn-sm">✏️ Edit</a>
                                    <a href="hapus.php?id=<?= $b['id'] ?>"
                                       class="btn btn-delete btn-sm"
                                       onclick="return confirm(<?= json_encode('Yakin ingin menghapus ' . $b['nama_barang'] . '? Tindakan ini tidak bisa dibatalkan.') ?>)"
                                        🗑️ Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer>
    &copy; <?= date('Y') ?> Inventaris App &mdash; Dibangun dengan PHP &amp; PDO
</footer>

</body>
</html>