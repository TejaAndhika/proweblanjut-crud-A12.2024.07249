<?php
// edit.php - Edit Barang + Ganti/Hapus Gambar

include __DIR__ . '/../../public/koneksi.php';
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /public/index.php?page=login');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /views/barang/daftar.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM barang WHERE id = :id");
$stmt->execute([':id' => $id]);
$barang = $stmt->fetch();

if (!$barang) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Data barang tidak ditemukan.'];
    header('Location: /views/barang/daftar.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── 1. Sanitasi & Ambil Input ──────────────────────────────────
    $nama_barang   = trim($_POST['nama_barang']   ?? '');
    $jumlah        = trim($_POST['jumlah']        ?? '');
    $harga         = trim($_POST['harga']         ?? '');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');
    $hapus_gambar  = isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] === '1';

    // ── 2. Validasi Input Sisi Server ─────────────────────────────
    if (empty($nama_barang)) {
        $errors[] = 'Nama barang wajib diisi.';
    } elseif (strlen($nama_barang) > 255) {
        $errors[] = 'Nama barang maksimal 255 karakter.';
    }

    if ($jumlah === '' || !ctype_digit($jumlah)) {
        $errors[] = 'Jumlah harus berupa angka bulat positif.';
    } elseif ((int)$jumlah < 0) {
        $errors[] = 'Jumlah tidak boleh negatif.';
    }

    if ($harga === '' || !is_numeric($harga)) {
        $errors[] = 'Harga harus berupa angka positif.';
    } elseif ((float)$harga < 0) {
        $errors[] = 'Harga tidak boleh negatif.';
    }

    if (empty($tanggal_masuk)) {
        $errors[] = 'Tanggal masuk wajib diisi.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_masuk)) {
        $errors[] = 'Format tanggal tidak valid.';
    }

    // ── 3. Tentukan nilai gambar akhir ────────────────────────────
    // Prioritas: (a) file baru diunggah → pakai file baru
    //            (b) checkbox hapus dicentang → set NULL
    //            (c) tidak ada aksi → pertahankan gambar lama
    $nama_file_gambar = $barang['gambar']; // Default: gambar lama
    $ada_file_baru    = isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($ada_file_baru) {
        // --- Ada file baru: validasi & siapkan nama unik ---
        $file     = $_FILES['gambar'];
        $max_size = 2 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah gambar (kode: ' . $file['error'] . ').';
        } else {
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mime     = $finfo->file($file['tmp_name']);
            $mime_map = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            if (!array_key_exists($mime, $mime_map)) {
                $errors[] = 'Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, atau WebP.';
            } elseif ($file['size'] > $max_size) {
                $errors[] = 'Ukuran gambar maksimal 2 MB.';
            } else {
                $nama_file_gambar = uniqid('barang_', true) . '.' . $mime_map[$mime];
            }
        }
    } elseif ($hapus_gambar) {
        // --- Tidak ada file baru, tapi user minta hapus gambar ---
        $nama_file_gambar = null;
    }

    // ── 4. Eksekusi jika tidak ada error ─────────────────────────
    if (empty($errors)) {
       $upload_dir = __DIR__ . '/../public/uploads/barang/';

        if ($ada_file_baru && $nama_file_gambar !== $barang['gambar']) {
            // Pindahkan file baru
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $nama_file_gambar)) {
                // Hapus file lama jika ada
                if ($barang['gambar'] && file_exists($upload_dir . $barang['gambar'])) {
                    unlink($upload_dir . $barang['gambar']);
                }
            } else {
                $errors[] = 'Gagal menyimpan file gambar ke server.';
                $nama_file_gambar = $barang['gambar']; // Rollback
            }
        } elseif ($hapus_gambar && $barang['gambar']) {
            // Hapus file lama dari disk
            $file_path = $upload_dir . $barang['gambar'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        if (empty($errors)) {
            $sql  = "UPDATE barang
                     SET nama_barang   = :nama,
                         jumlah        = :jumlah,
                         harga         = :harga,
                         tanggal_masuk = :tgl,
                         gambar        = :gambar
                     WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nama'   => $nama_barang,
                ':jumlah' => (int)$jumlah,
                ':harga'  => (float)$harga,
                ':tgl'    => $tanggal_masuk,
                ':gambar' => $nama_file_gambar,
                ':id'     => $id,
            ]);

            $_SESSION['flash'] = ['type' => 'success', 'msg' => '✅ Data barang berhasil diperbarui!'];
            header('Location: /views/barang/daftar.php');
            exit;
        }
    }

    // Kembalikan nilai ke form jika ada error
    $barang['nama_barang']   = $nama_barang;
    $barang['jumlah']        = $jumlah;
    $barang['harga']         = $harga;
    $barang['tanggal_masuk'] = $tanggal_masuk;
}

// Apakah barang saat ini punya gambar?
$punya_gambar = !empty($barang['gambar']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang — Inventaris App</title>
    <link rel="stylesheet" href="../../public/style.css">
    <style>
        .img-preview-wrap { margin-top: 10px; }
        .img-preview-wrap img {
            max-width: 200px;
            max-height: 160px;
            border-radius: 8px;
            border: 2px solid var(--border);
            object-fit: cover;
        }
        .img-current-label { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 6px; }
        .file-hint { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; }
        input[type="file"] {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            font-family: inherit;
            font-size: 0.875rem;
            color: var(--text);
            width: 100%;
            cursor: pointer;
        }
        input[type="file"]:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
            outline: none;
        }

        /* Area gambar saat ini */
        .gambar-saat-ini {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 12px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .gambar-saat-ini img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            flex-shrink: 0;
        }
        .gambar-saat-ini-info { display: flex; flex-direction: column; gap: 6px; justify-content: center; }
        .gambar-saat-ini-info small { font-size: 0.75rem; color: var(--text-muted); }

        /* Tombol hapus gambar saat ini */
        .btn-hapus-gambar-db {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 13px;
            background: rgba(239,68,68,0.12);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-hapus-gambar-db:hover { background: rgba(239,68,68,0.24); }
        .btn-hapus-gambar-db.aktif {
            background: rgba(239,68,68,0.30);
            border-color: rgba(239,68,68,0.55);
        }
        .hapus-warning {
            font-size: 0.78rem;
            color: #f87171;
            display: none;
            margin-top: 4px;
        }
        .hapus-warning.tampil { display: block; }

        /* Tombol batalkan pilihan file baru */
        .btn-hapus-preview {
            display: none;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
            padding: 5px 13px;
            background: rgba(239,68,68,0.12);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-hapus-preview:hover { background: rgba(239,68,68,0.24); }
    </style>
</head>
<body>
<header>
    <div class="header-inner">
        <a href="/public/index.php?page=barang" class="logo">
            <div class="logo-icon">📦</div>
            Inventaris<span>App</span>
        </a>
        <nav>
            <!-- navigasi -->
            <a href="/public/index.php?page=barang">Daftar Barang</a>
            <a href="/public/index.php?page=tambah">+ Tambah</a>

            <!-- tombol batal -->
            <a href="/public/index.php?page=barang" class="btn btn-secondary">← Batal</a>
        </nav>
    </div>
</header>

<main>
    <div class="page-title">
        <h1>✏️ Edit Data Barang</h1>
        <p>Perbarui informasi barang <strong><?= htmlspecialchars($barang['nama_barang']) ?></strong> (ID: #<?= $id ?>).</p>
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
            <h2>Form Edit Barang</h2>
            <span style="font-size:.8rem;color:var(--text-muted)">ID #<?= $id ?></span>
        </div>
        <form method="POST" action="edit.php?id=<?= $id ?>" enctype="multipart/form-data" id="form-edit">
            <!-- Hidden field: dikirim bernilai "1" jika user klik tombol hapus gambar -->
            <input type="hidden" name="hapus_gambar" id="hapus_gambar" value="0">

            <div class="form-grid">
                <div class="form-group full">
                    <label for="nama_barang">Nama Barang</label>
                    <input type="text" id="nama_barang" name="nama_barang"
                           value="<?= htmlspecialchars($barang['nama_barang']) ?>"
                           maxlength="255" required autofocus>
                </div>
                <div class="form-group">
                    <label for="jumlah">Jumlah (Unit)</label>
                    <input type="number" id="jumlah" name="jumlah"
                           value="<?= htmlspecialchars($barang['jumlah']) ?>"
                           min="0" step="1" required>
                </div>
                <div class="form-group">
                    <label for="harga">Harga Satuan (Rp)</label>
                    <input type="number" id="harga" name="harga"
                           value="<?= htmlspecialchars($barang['harga']) ?>"
                           min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="tanggal_masuk">Tanggal Masuk</label>
                    <input type="date" id="tanggal_masuk" name="tanggal_masuk"
                           value="<?= htmlspecialchars($barang['tanggal_masuk']) ?>" required>
                </div>

                <div class="form-group full">
                    <label>Gambar Barang</label>

                    <?php if ($punya_gambar): ?>
                    <!-- Tampilkan gambar yang sudah ada di database -->
                    <div class="gambar-saat-ini" id="area-gambar-saat-ini">
                        <img src="uploads/<?= htmlspecialchars($barang['gambar']) ?>"
                             alt="<?= htmlspecialchars($barang['nama_barang']) ?>"
                             id="img-db">
                        <div class="gambar-saat-ini-info">
                            <small>Gambar tersimpan saat ini</small>
                            <small style="word-break:break-all;font-family:monospace;font-size:0.7rem;color:var(--accent)">
                                <?= htmlspecialchars($barang['gambar']) ?>
                            </small>
                            <!-- Tombol hapus gambar dari database -->
                            <button type="button"
                                    id="btn-hapus-db"
                                    class="btn-hapus-gambar-db"
                                    onclick="toggleHapusGambarDB()">
                                🗑️ Hapus Gambar Ini
                            </button>
                            <span class="hapus-warning" id="hapus-warning">
                                ⚠️ Gambar akan dihapus permanen saat form disimpan.
                                <a href="#" onclick="batalHapusGambarDB(event)" style="color:#fbbf24;margin-left:4px">Batalkan</a>
                            </span>
                        </div>
                    </div>
                    <?php else: ?>
                    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:8px">
                        🖼️ Belum ada gambar untuk barang ini.
                    </p>
                    <?php endif; ?>

                    <!-- Input file untuk upload gambar baru -->
                    <label for="gambar" style="margin-top:<?= $punya_gambar ? '10px' : '0' ?>;display:block">
                        <?= $punya_gambar ? 'Ganti dengan gambar baru' : 'Unggah gambar' ?>
                        <span style="color:var(--text-muted);font-weight:400">(opsional)</span>
                    </label>
                    <input type="file" id="gambar" name="gambar"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="previewGambarBaru(event)"
                           style="margin-top:6px">
                    <span class="file-hint">Format: JPG, PNG, GIF, WebP. Maks: 2 MB.</span>

                    <!-- Preview gambar baru sebelum disimpan -->
                    <div class="img-preview-wrap" id="preview-baru-wrap">
                        <img id="img-preview-baru" src="#" alt="Preview gambar baru" style="display:none">
                    </div>
                    <button type="button" id="btn-hapus-preview" class="btn-hapus-preview"
                            onclick="batalPilihGambarBaru()">
                        🗑️ Batalkan Pilihan Gambar Baru
                    </button>
                </div>
            </div>

            <div class="form-actions">
                <a href="/views/barang/daftar.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">💾 Perbarui Data</button>
            </div>
        </form>
    </div>
</main>

<footer>&copy; <?= date('Y') ?> Inventaris App</footer>

<script>
// ── Hapus gambar yang tersimpan di database ───────────────────────
function toggleHapusGambarDB() {
    const hidden   = document.getElementById('hapus_gambar');
    const btnHapus = document.getElementById('btn-hapus-db');
    const warning  = document.getElementById('hapus-warning');
    const imgDB    = document.getElementById('img-db');

    hidden.value = '1';
    btnHapus.classList.add('aktif');
    btnHapus.textContent = '✔️ Akan Dihapus';
    btnHapus.disabled = true;
    warning.classList.add('tampil');
    if (imgDB) imgDB.style.opacity = '0.3';
}

function batalHapusGambarDB(e) {
    e.preventDefault();
    const hidden   = document.getElementById('hapus_gambar');
    const btnHapus = document.getElementById('btn-hapus-db');
    const warning  = document.getElementById('hapus-warning');
    const imgDB    = document.getElementById('img-db');

    hidden.value = '0';
    btnHapus.classList.remove('aktif');
    btnHapus.innerHTML = '🗑️ Hapus Gambar Ini';
    btnHapus.disabled = false;
    warning.classList.remove('tampil');
    if (imgDB) imgDB.style.opacity = '1';
}

// ── Preview & batalkan pilihan gambar baru ────────────────────────
function previewGambarBaru(event) {
    const input    = event.target;
    const preview  = document.getElementById('img-preview-baru');
    const btnHapus = document.getElementById('btn-hapus-preview');

    // Jika user memilih file baru, batalkan flag hapus-gambar-db otomatis
    batalHapusGambarDB({ preventDefault: () => {} });

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src           = e.target.result;
            preview.style.display = 'block';
            btnHapus.style.display = 'inline-flex';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        sembunyikanPreviewBaru();
    }
}

function batalPilihGambarBaru() {
    document.getElementById('gambar').value = '';
    sembunyikanPreviewBaru();
}

function sembunyikanPreviewBaru() {
    const preview  = document.getElementById('img-preview-baru');
    const btnHapus = document.getElementById('btn-hapus-preview');
    preview.src            = '#';
    preview.style.display  = 'none';
    btnHapus.style.display = 'none';
}
</script>
</body>
</html>