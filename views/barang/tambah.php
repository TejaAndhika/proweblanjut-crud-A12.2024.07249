<?php
// tambah.php - Tambah Barang + Upload Gambar

include __DIR__ . '/../../public/koneksi.php';
session_start();

$errors = [];
$data   = [
    'nama_barang'   => '',
    'jumlah'        => '',
    'harga'         => '',
    'tanggal_masuk' => date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── 1. Sanitasi & Ambil Input ─────────────────────────────────
    $data['nama_barang']   = trim($_POST['nama_barang']   ?? '');
    $data['jumlah']        = trim($_POST['jumlah']        ?? '');
    $data['harga']         = trim($_POST['harga']         ?? '');
    $data['tanggal_masuk'] = trim($_POST['tanggal_masuk'] ?? '');

    // ── 2. Validasi Input Sisi Server ─────────────────────────────
    if (empty($data['nama_barang'])) {
        $errors[] = 'Nama barang wajib diisi.';
    } elseif (strlen($data['nama_barang']) > 255) {
        $errors[] = 'Nama barang maksimal 255 karakter.';
    }

    if ($data['jumlah'] === '' || !ctype_digit($data['jumlah'])) {
        $errors[] = 'Jumlah harus berupa angka bulat positif.';
    } elseif ((int)$data['jumlah'] < 0) {
        $errors[] = 'Jumlah tidak boleh negatif.';
    }

    if ($data['harga'] === '' || !is_numeric($data['harga'])) {
        $errors[] = 'Harga harus berupa angka positif.';
    } elseif ((float)$data['harga'] < 0) {
        $errors[] = 'Harga tidak boleh negatif.';
    }

    if (empty($data['tanggal_masuk'])) {
        $errors[] = 'Tanggal masuk wajib diisi.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['tanggal_masuk'])) {
        $errors[] = 'Format tanggal tidak valid.';
    }

    // ── 3. Proses Upload Gambar ───────────────────────────────────
    $nama_file_gambar = null;

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {

        $file     = $_FILES['gambar'];
        $max_size = 2 * 1024 * 1024; // 2 MB

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
                $ext              = $mime_map[$mime];
                $nama_file_gambar = uniqid('barang_', true) . '.' . $ext;
            }
        }
    }

    // ── 4. Simpan ke Database jika tidak ada error ─────────────────
    if (empty($errors)) {
        if ($nama_file_gambar !== null) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $nama_file_gambar)) {
                $errors[] = 'Gagal menyimpan file gambar ke server.';
                $nama_file_gambar = null;
            }
        }

        if (empty($errors)) {
            $sql  = "INSERT INTO barang (nama_barang, jumlah, harga, tanggal_masuk, gambar)
                     VALUES (:nama, :jumlah, :harga, :tgl, :gambar)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nama'   => $data['nama_barang'],
                ':jumlah' => (int)$data['jumlah'],
                ':harga'  => (float)$data['harga'],
                ':tgl'    => $data['tanggal_masuk'],
                ':gambar' => $nama_file_gambar,
            ]);

            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => '✅ Barang "' . htmlspecialchars($data['nama_barang']) . '" berhasil ditambahkan!',
            ];
            header('Location: /views/barang/daftar.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang — Inventaris App</title>
    <link rel="stylesheet" href="../../public/style.css">
    <style>
        .img-preview-wrap { margin-top: 10px; }
        .img-preview-wrap img {
            max-width: 200px;
            max-height: 160px;
            border-radius: 8px;
            border: 2px solid var(--border);
            object-fit: cover;
            display: none;
        }
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
        /* Tombol hapus preview */
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
        <a href="../../public/index.php" class="logo">
            <div class="logo-icon">📦</div>
            Inventaris<span>App</span>
        </a>
        <nav>
            <a href="/views/barang/daftar.php">Daftar Barang</a>
            <a href="/views/barang/tambah.php" class="active">+ Tambah</a>
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
        <form method="POST" action="/views/barang/tambah.php" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group full">
                    <label for="nama_barang">Nama Barang</label>
                    <input type="text" id="nama_barang" name="nama_barang"
                           placeholder="Contoh: Laptop ASUS VivoBook 15"
                           value="<?= htmlspecialchars($data['nama_barang']) ?>"
                           maxlength="255" required autofocus>
                </div>
                <div class="form-group">
                    <label for="jumlah">Jumlah (Unit)</label>
                    <input type="number" id="jumlah" name="jumlah"
                           placeholder="0"
                           value="<?= htmlspecialchars($data['jumlah']) ?>"
                           min="0" step="1" required>
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
                <div class="form-group full">
                    <label for="gambar">Gambar Barang <span style="color:var(--text-muted);font-weight:400">(opsional)</span></label>
                    <input type="file" id="gambar" name="gambar"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="previewImage(event)">
                    <span class="file-hint">Format: JPG, PNG, GIF, WebP. Maks: 2 MB.</span>
                    <!-- Preview gambar yang belum diupload -->
                    <div class="img-preview-wrap">
                        <img id="img-preview" src="#" alt="Preview Gambar">
                    </div>
                    <!-- Tombol hapus: hanya tampil setelah gambar dipilih -->
                    <button type="button" id="btn-hapus-preview" class="btn-hapus-preview"
                            onclick="hapusPreviewGambar()">
                        🗑️ Batalkan Pilihan Gambar
                    </button>
                </div>
            </div>
            <div class="form-actions">
                <a href="/views/barang/daftar.php" class="btn btn-secondary">← Batal</a>
                <button type="submit" class="btn btn-primary">✅ Simpan Barang</button>
            </div>
        </form>
    </div>
</main>

<footer>
    &copy; <?= date('Y') ?> Inventaris App — Dibangun dengan PHP &amp; PDO
</footer>

<script>
function previewImage(event) {
    const input   = event.target;
    const preview = document.getElementById('img-preview');
    const btnHapus = document.getElementById('btn-hapus-preview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src          = e.target.result;
            preview.style.display = 'block';
            btnHapus.style.display = 'inline-flex';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        sembunyikanPreview();
    }
}

function hapusPreviewGambar() {
    // Reset input file sehingga tidak ada file yang dikirim
    const input = document.getElementById('gambar');
    input.value = '';          // Kosongkan pilihan file
    sembunyikanPreview();
}

function sembunyikanPreview() {
    const preview  = document.getElementById('img-preview');
    const btnHapus = document.getElementById('btn-hapus-preview');
    preview.src            = '#';
    preview.style.display  = 'none';
    btnHapus.style.display = 'none';
}
</script>
</body>
</html>