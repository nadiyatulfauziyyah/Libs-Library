
<?php
include 'db_config.php';

if ($conn->connect_error) {
    die("KONEKSI GAGAL: " . $conn->connect_error);
}

echo "KONEKSI BERHASIL!";
echo "<br>Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0];
echo "<br>Port: " . $conn->port;
?>