<?php
// File: export_bibliografi.php — Ekspor katalog bibliografi + stok ke CSV
session_start();
if (!isset($_SESSION["user_nama"])) { header("Location: login.php"); exit; }
include 'db_config.php';

$sql = "SELECT bg.ID, bg.Judul, bg.Pengarang, bg.Kategori, bg.Tahun,
               COALESCE(b.stok_total,0) AS stok_total, COALESCE(b.stok_tersedia,0) AS stok_tersedia
        FROM tbl_blibliografi bg
        LEFT JOIN tbl_buku b ON bg.ID = b.id_bibliografi
        ORDER BY bg.ID ASC";
$result = $conn->query($sql);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="katalog_bibliografi_' . date('Ymd') . '.csv"');
echo "\xEF\xBB\xBF"; // BOM agar terbaca benar di Excel
$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Judul', 'Pengarang', 'Kategori', 'Tahun', 'Stok Total', 'Stok Tersedia']);
while ($r = $result->fetch_assoc()) {
    fputcsv($out, [$r['ID'], $r['Judul'], $r['Pengarang'], $r['Kategori'], $r['Tahun'], $r['stok_total'], $r['stok_tersedia']]);
}
fclose($out);
$conn->close();
