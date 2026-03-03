<?php
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'inventaris_db';
    
    $conn = new mysqli($host, $user, $pass, $dbname);

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Koneksi gagal: " . $e->getMessage());
    }
?>