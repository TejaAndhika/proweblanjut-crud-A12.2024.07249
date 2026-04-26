<?php
// models/UserModel.php - Semua query SQL terkait tabel users

class UserModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Cari user berdasarkan username
    public function getUserByUsername($username)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    // Cari user berdasarkan id
    public function getUserById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Cari user berdasarkan remember_token
    public function getUserByToken($token)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE remember_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    // Cek apakah username sudah dipakai
    public function isUsernameTaken($username)
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        return (bool) $stmt->fetch();
    }

    // Daftarkan user baru
    public function registerUser($username, $password)
    {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt   = $this->conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        return $stmt->execute([':username' => $username, ':password' => $hashed]);
    }

    // Simpan remember_token ke database
    public function saveToken($id, $token)
    {
        $stmt = $this->conn->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
        return $stmt->execute([':token' => $token, ':id' => $id]);
    }

    // Hapus remember_token dari database (saat logout)
    public function clearToken($token)
    {
        $stmt = $this->conn->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = :token");
        return $stmt->execute([':token' => $token]);
    }
}
?>