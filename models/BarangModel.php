<?php
// models/BarangModel.php - Semua query SQL terkait tabel barang

class BarangModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Ambil semua barang
    public function getAllBarang()
    {
        $stmt = $this->conn->query("SELECT * FROM barang ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    // Ambil 1 barang berdasarkan id
    public function getBarangById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM barang WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Tambah barang baru
    public function tambahBarang($nama_barang, $jumlah, $harga, $tanggal_masuk, $gambar)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO barang (nama_barang, jumlah, harga, tanggal_masuk, gambar)
             VALUES (:nama, :jumlah, :harga, :tgl, :gambar)"
        );
        return $stmt->execute([
            ':nama'   => $nama_barang,
            ':jumlah' => (int) $jumlah,
            ':harga'  => (float) $harga,
            ':tgl'    => $tanggal_masuk,
            ':gambar' => $gambar,
        ]);
    }

    // Update barang
    public function editBarang($id, $nama_barang, $jumlah, $harga, $tanggal_masuk, $gambar)
    {
        $stmt = $this->conn->prepare(
            "UPDATE barang
             SET nama_barang   = :nama,
                 jumlah        = :jumlah,
                 harga         = :harga,
                 tanggal_masuk = :tgl,
                 gambar        = :gambar
             WHERE id = :id"
        );
        return $stmt->execute([
            ':nama'   => $nama_barang,
            ':jumlah' => (int) $jumlah,
            ':harga'  => (float) $harga,
            ':tgl'    => $tanggal_masuk,
            ':gambar' => $gambar,
            ':id'     => $id,
        ]);
    }

    // Hapus barang
    public function hapusBarang($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM barang WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
?>