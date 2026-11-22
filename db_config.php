
<?php
// Konfigurasi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "libs";
$port = 3309;

// Membuat koneksi
$conn = new mysqli(
    $servername,
    $username,
    $password,
    $dbname,
    $port
);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Mengatur karakter database
$conn->set_charset("utf8");

// Pastikan kolom Cover ada di tbl_blibliografi (dibuat otomatis jika belum ada)
$cekCover = $conn->query("SHOW COLUMNS FROM tbl_blibliografi LIKE 'Cover'");
if ($cekCover && $cekCover->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_blibliografi ADD COLUMN Cover VARCHAR(255) NULL DEFAULT NULL");
}

// Koneksi berhasil
?>
