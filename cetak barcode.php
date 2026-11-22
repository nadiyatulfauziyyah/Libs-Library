<?php
// File: cetak_barcode.php — Cetak label barcode (Code128) per bibliografi.
// Gunakan Ctrl+P lalu pilih "Save as PDF" untuk menyimpan sebagai PDF.
session_start();
if (!isset($_SESSION["user_nama"])) { header("Location: login.php"); exit; }
include 'db_config.php';

$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
if (!$ids) { echo "Tidak ada buku dipilih."; exit; }

$in = implode(',', $ids); // aman: sudah dipaksa integer
$result = $conn->query("SELECT ID, Judul, Pengarang FROM tbl_blibliografi WHERE ID IN ($in) ORDER BY ID ASC");
$e = function($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Label Barcode Koleksi — libs</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<style>
body{font-family:Arial,sans-serif;margin:20px;color:#222}
.bar{margin-bottom:16px}
.bar button{padding:8px 14px;cursor:pointer}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.label{border:1px dashed #999;padding:8px;text-align:center;page-break-inside:avoid}
.label .judul{font-size:12px;font-weight:bold;line-height:1.2;max-height:2.4em;overflow:hidden}
.label .pengarang{font-size:10px;color:#555;margin-bottom:4px}
.label svg{width:100%;height:60px}
@media print{.bar{display:none}body{margin:8mm}}
</style>
</head>
<body>
<div class="bar">
    <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    <button onclick="window.close()">Tutup</button>
</div>
<div class="grid">
<?php while ($r = $result->fetch_assoc()): $kode = 'BK' . str_pad($r['ID'], 5, '0', STR_PAD_LEFT); ?>
    <div class="label">
        <div class="judul"><?= $e($r['Judul']) ?></div>
        <div class="pengarang"><?= $e($r['Pengarang']) ?></div>
        <svg class="barcode" data-kode="<?= $e($kode) ?>"></svg>
    </div>
<?php endwhile; ?>
</div>
<script>
document.querySelectorAll('svg.barcode').forEach(el => {
    JsBarcode(el, el.dataset.kode, {format: 'CODE128', displayValue: true, fontSize: 14, height: 50, margin: 0});
});
</script>
</body>
</html>
<?php $conn->close(); ?>
