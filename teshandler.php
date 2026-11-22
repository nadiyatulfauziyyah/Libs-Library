
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "TES PHP BERHASIL\n";

include 'db_config.php';

echo "TES DATABASE BERHASIL\n";

$cek = $conn->query("SHOW COLUMNS FROM tbl_anggota");

if (!$cek) {
    die("ERROR TABEL: " . $conn->error);
}

echo "DAFTAR KOLOM TABEL:\n";

while ($kolom = $cek->fetch_assoc()) {
    echo $kolom['Field'] . "\n";
}
?>