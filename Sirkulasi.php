<?php
session_start();
include 'db_config.php'; 

// Cek login
if (!isset($_SESSION["user_nama"])) {
    header("Location: login.php");
    exit;
}

// Konfigurasi Sirkulasi (Menggunakan define untuk menghindari potensi Parse Error)
define('BATAS_HARI', 7);
define('DENDA_PER_HARI', 2000);

$message = '';
$error = '';
$conn->set_charset("utf8mb4");
mysqli_report(MYSQLI_REPORT_OFF); // query gagal tidak membuat halaman fatal; ditangani diagnosa di bawah

// Fungsi untuk menghitung denda
function hitungDenda($tgl_pinjam) {
    // Akses konstanta menggunakan namanya
    $batas_hari = BATAS_HARI; 
    $denda_per_hari = DENDA_PER_HARI;
    
    // Pastikan tgl_pinjam adalah string format YYYY-MM-DD
    $tgl_pinjam_dt = new DateTime($tgl_pinjam);
    $tgl_sekarang = new DateTime();
    $diff = $tgl_pinjam_dt->diff($tgl_sekarang);
    $hari_pinjam = $diff->days;
    
    if ($hari_pinjam > $batas_hari) {
        $hari_telat = $hari_pinjam - $batas_hari;
        return $hari_telat * $denda_per_hari;
    }
    return 0;
}


// --- LOGIKA FORM HANDLING ---

// 1. PINJAM BUKU
if (isset($_POST['pinjam'])) {
    $id_anggota = isset($_POST['id_anggota']) ? $_POST['id_anggota'] : null;
	$id_buku = isset($_POST['id_buku']) ? $_POST['id_buku'] : null;
    
    if (empty($id_anggota) || empty($id_buku)) {
        $error = "ID Anggota dan ID Buku harus diisi.";
    } else {
        // Ambil stok tersedia dari tbl_buku
        $stmt_stok = $conn->prepare("SELECT stok_tersedia FROM tbl_buku WHERE id = ?");
        $stmt_stok->bind_param("i", $id_buku);
        $stmt_stok->execute();
        $result_stok = $stmt_stok->get_result();
        $buku_data = $result_stok->fetch_assoc();
        $stmt_stok->close();
        
        if ($buku_data && $buku_data['stok_tersedia'] > 0) {
            $tgl_pinjam = date('Y-m-d');
            $tgl_kembali_target = date('Y-m-d', strtotime("+" . BATAS_HARI . " days"));
            
            // Masukkan ke tabel sirkulasi
            $stmt_pinjam = $conn->prepare("INSERT INTO tbl_sirkulasi (id_anggota, id_buku, tgl_pinjam, tgl_kembali_target) VALUES (?, ?, ?, ?)");
            $stmt_pinjam->bind_param("iiss", $id_anggota, $id_buku, $tgl_pinjam, $tgl_kembali_target);
            
            if ($stmt_pinjam->execute()) {
                // Kurangi stok di tbl_buku
                $conn->query("UPDATE tbl_buku SET stok_tersedia = stok_tersedia - 1 WHERE id = '$id_buku'");
                $message = "Buku berhasil dipinjam! Target kembali: " . date('d M Y', strtotime($tgl_kembali_target));
            } else {
                $error = "Gagal meminjam buku: " . $stmt_pinjam->error;
            }
            $stmt_pinjam->close();
            
        } else {
            $error = "Stok buku tidak tersedia.";
        }
    }
}

// 2. KEMBALIKAN BUKU
if (isset($_POST['kembalikan'])) {
    $id_sirkulasi = isset($_POST['id_sirkulasi']) ? $_POST['id_sirkulasi'] : null;
    
    if (empty($id_sirkulasi)) {
        $error = "Pilih buku yang akan dikembalikan.";
    } else {
        // Ambil data transaksi (termasuk id_buku fisik dan tgl_pinjam)
        $stmt_trans = $conn->prepare("SELECT id_buku, tgl_pinjam FROM tbl_sirkulasi WHERE id = ? AND tgl_kembali_aktual IS NULL");
        $stmt_trans->bind_param("i", $id_sirkulasi);
        $stmt_trans->execute();
        $result_trans = $stmt_trans->get_result();
        $trans_data = $result_trans->fetch_assoc();
        $stmt_trans->close();
        
        if ($trans_data) {
            $denda = hitungDenda($trans_data['tgl_pinjam']);
            $tgl_kembali_aktual = date('Y-m-d H:i:s');
            $id_buku = $trans_data['id_buku'];
            
            // Update tabel sirkulasi
            $stmt_update = $conn->prepare("UPDATE tbl_sirkulasi SET tgl_kembali_aktual = ?, denda = ? WHERE id = ?");
            $stmt_update->bind_param("sii", $tgl_kembali_aktual, $denda, $id_sirkulasi);
            
            if ($stmt_update->execute()) {
                // Tambah stok di tbl_buku
                $conn->query("UPDATE tbl_buku SET stok_tersedia = stok_tersedia + 1 WHERE id = '$id_buku'");
                
                $msg = "Buku berhasil dikembalikan!";
                if ($denda > 0) {
                    $msg .= " Denda keterlambatan: *Rp " . number_format($denda, 0, ',', '.') . "*";
                }
                $message = $msg;
            } else {
                $error = "Gagal mengembalikan buku: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $error = "Transaksi tidak ditemukan atau sudah dikembalikan.";
        }
    }
}


// --- LOGIKA AMBIL DATA (Untuk Dropdown dan Tabel) ---

// 1. Ambil semua anggota (untuk dropdown Pinjam)
$anggota_list = [];
$result_anggota = $conn->query("SELECT id, nama, nim_nik FROM tbl_anggota ORDER BY nama ASC");
if ($result_anggota) {
    while ($row = $result_anggota->fetch_assoc()) {
        $anggota_list[] = $row;
    }
}

// 2. Ambil buku yang tersedia (stok_tersedia > 0)
$buku_tersedia_list = [];
$sql_buku_tersedia = "
    SELECT 
        b.id,               
        bg.judul            
    FROM tbl_buku b
    JOIN tbl_blibliografi bg ON b.id_bibliografi = bg.id
    WHERE b.stok_tersedia > 0 
    ORDER BY bg.judul ASC
";
$result_buku_tersedia = $conn->query($sql_buku_tersedia);
if ($result_buku_tersedia) {
    while ($row = $result_buku_tersedia->fetch_assoc()) {
        $buku_tersedia_list[] = $row;
    }
}

// 3. Ambil daftar transaksi yang BELUM dikembalikan (untuk Tabel Aktif)
// (LEFT JOIN supaya transaksi tetap tampil walau data anggota/buku sudah tidak ada)
$sql_active_loans = "
    SELECT 
        s.id AS id_sirkulasi,
        COALESCE(bg.judul, '(judul tidak ditemukan)') AS judul,
        COALESCE(a.nama, CONCAT('Anggota #', s.id_anggota)) AS nama_anggota,
        s.tgl_pinjam,
        s.tgl_kembali_target
    FROM tbl_sirkulasi s
    LEFT JOIN tbl_anggota a ON s.id_anggota = a.id
    LEFT JOIN tbl_buku b ON s.id_buku = b.id
    LEFT JOIN tbl_blibliografi bg ON b.id_bibliografi = bg.id
    WHERE s.tgl_kembali_aktual IS NULL
    ORDER BY s.tgl_pinjam DESC";

$result_active_loans = $conn->query($sql_active_loans);
$active_loans = [];
if ($result_active_loans) {
    while ($row = $result_active_loans->fetch_assoc()) {
        // Hitung denda real-time
        $row['denda_saat_ini'] = hitungDenda($row['tgl_pinjam']);
        $active_loans[] = $row;
    }
}

// 4. Ambil SEMUA buku dan stoknya untuk tampilan inventaris
$sql_all_books = "
    SELECT 
        bg.id AS id_bibliografi,
        bg.judul,
        bg.pengarang,
        bg.tahun,
        b.stok_total,
        b.stok_tersedia
    FROM tbl_blibliografi bg
    LEFT JOIN tbl_buku b ON bg.id = b.id_bibliografi
    ORDER BY bg.judul ASC
";
$result_all_books = $conn->query($sql_all_books);
$all_books = [];
if ($result_all_books) {
    while ($row = $result_all_books->fetch_assoc()) {
        $all_books[] = $row;
    }
}

// --- DATA UNTUK DASHBOARD (hanya SELECT, tidak mengubah database) ---

// Kartu statistik
$jumlah_judul = count($all_books);
$total_eksemplar = 0;
$total_tersedia = 0;
foreach ($all_books as $bk) {
    $total_eksemplar += (int)$bk['stok_total'];
    $total_tersedia += (int)$bk['stok_tersedia'];
}
$aktif_total = count($active_loans);
$aktif_telat = 0;
$denda_berjalan = 0;
foreach ($active_loans as $l) {
    if ($l['denda_saat_ini'] > 0) {
        $aktif_telat++;
        $denda_berjalan += $l['denda_saat_ini'];
    }
}
$aktif_tepat = $aktif_total - $aktif_telat;

// Donut status transaksi
$kembali_tepat = 0;
$kembali_telat = 0;
$res_status = $conn->query("
    SELECT
        COALESCE(SUM(tgl_kembali_aktual IS NOT NULL AND denda = 0), 0) AS tepat,
        COALESCE(SUM(tgl_kembali_aktual IS NOT NULL AND denda > 0), 0) AS telat
    FROM tbl_sirkulasi
");
if ($res_status && ($r = $res_status->fetch_assoc())) {
    $kembali_tepat = (int)$r['tepat'];
    $kembali_telat = (int)$r['telat'];
}
$total_transaksi = $kembali_tepat + $kembali_telat + $aktif_total;

$donut = [
    ['Kembali tepat waktu', $kembali_tepat, '#53664e'],
    ['Kembali terlambat',   $kembali_telat, '#b68b69'],
    ['Masih dipinjam',      $aktif_tepat,   '#d9c3a5'],
    ['Terlambat aktif',     $aktif_telat,   '#3b1722'],
];

// Judul paling sering dipinjam
$populer = [];
$res_populer = $conn->query("
    SELECT bg.judul, COUNT(*) AS jml
    FROM tbl_sirkulasi s
    JOIN tbl_buku b ON s.id_buku = b.id
    JOIN tbl_blibliografi bg ON b.id_bibliografi = bg.id
    GROUP BY bg.id, bg.judul
    ORDER BY jml DESC, bg.judul ASC
    LIMIT 5
");
if ($res_populer) {
    while ($row = $res_populer->fetch_assoc()) {
        $populer[] = $row;
    }
}
$populer_max = $populer ? (int)$populer[0]['jml'] : 1;

// Riwayat transaksi (5 terbaru, atau semua bila ?semua=1)
$lihat_semua = isset($_GET['semua']);
$sql_riwayat = "
    SELECT
        s.id,
        COALESCE(bg.judul, '(judul tidak ditemukan)') AS judul,
        COALESCE(a.nama, CONCAT('Anggota #', s.id_anggota)) AS nama_anggota,
        s.tgl_pinjam,
        s.tgl_kembali_aktual,
        s.denda
    FROM tbl_sirkulasi s
    LEFT JOIN tbl_anggota a ON s.id_anggota = a.id
    LEFT JOIN tbl_buku b ON s.id_buku = b.id
    LEFT JOIN tbl_blibliografi bg ON b.id_bibliografi = bg.id
    ORDER BY s.id DESC" . ($lihat_semua ? "" : " LIMIT 5");
$riwayat = [];
$res_riwayat = $conn->query($sql_riwayat);
if ($res_riwayat) {
    while ($row = $res_riwayat->fetch_assoc()) {
        $riwayat[] = $row;
    }
}

// --- DIAGNOSA KONEKSI & DATA (hanya membaca, tidak mengubah apa pun) ---
function jumlahBaris($conn, $tb) {
    try {
        $q = @$conn->query("SELECT COUNT(*) AS n FROM `$tb`");
        if ($q && ($r = $q->fetch_assoc())) { return (int)$r['n']; }
    } catch (Throwable $e) {}
    return null; // tabel tidak ditemukan
}
$diag_db = '';
try {
    $q = @$conn->query("SELECT DATABASE() AS db");
    if ($q && ($r = $q->fetch_assoc())) { $diag_db = (string)$r['db']; }
} catch (Throwable $e) {}
$diag_masalah = [];
foreach (['tbl_anggota', 'tbl_buku', 'tbl_blibliografi', 'tbl_sirkulasi'] as $tb) {
    $n = jumlahBaris($conn, $tb);
    if ($n === null) { $diag_masalah[] = "$tb (tabel tidak ditemukan)"; }
    elseif ($n === 0 && $tb !== 'tbl_sirkulasi') { $diag_masalah[] = "$tb (tabel kosong)"; }
}
$diag_lain = [];
if ($diag_masalah) {
    try {
        $q = @$conn->query("SELECT table_schema AS db, COUNT(*) AS c FROM information_schema.tables
            WHERE table_name IN ('tbl_anggota','tbl_buku','tbl_blibliografi','tbl_sirkulasi')
            AND table_schema <> DATABASE() GROUP BY table_schema HAVING c >= 2");
        while ($q && ($r = $q->fetch_assoc())) { $diag_lain[] = $r['db']; }
    } catch (Throwable $e) {}
}

$conn->close();

// Helper tampilan
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }
function tgl($d) { return date('d M Y', strtotime($d)); }
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Libs — Sirkulasi</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/libs.css?v=<?= @filemtime(__DIR__ . '/assets/libs.css') ?>">

<style>
/* libs EDITORIAL ADMIN THEME */
:root{--bn-paper:#f4eadf;--bn-cream:#fffaf3;--bn-ink:#302321;--bn-wine:#401d28;--bn-muted:#82736a;--bn-line:#dfd0c1;--bn-gold:#b58b60}
body{background:var(--bn-paper)!important;color:var(--bn-ink)!important;font-family:'DM Sans',sans-serif!important;margin:0}
main{max-width:1280px!important;margin:34px auto!important;padding:0 clamp(18px,4vw,56px)!important}
h1,h2,h3{font-family:'Playfair Display',Georgia,serif!important;color:var(--bn-wine)!important;font-weight:400!important}
button{border-radius:0!important;font-family:'DM Sans',sans-serif!important;letter-spacing:.5px}
input,select,textarea{background:#fffaf3!important;border:1px solid var(--bn-line)!important;border-radius:0!important;color:var(--bn-ink)!important;padding:11px!important}
table{width:100%;background:var(--bn-cream)!important;border-collapse:collapse!important;border-radius:0!important;overflow:hidden}
th{background:var(--bn-wine)!important;color:#fff7ee!important;font:600 10px 'DM Sans',sans-serif!important;letter-spacing:1px;text-transform:uppercase}
td,th{padding:14px!important;border-bottom:1px solid var(--bn-line)!important;font-size:12px}
tr:hover{background:#f4e9dd!important}
.alert{border-radius:0!important;padding:12px 16px!important;margin:16px 0!important;font-size:14px}
.alert.success{background:#e8eee2!important;color:#40553b!important;border:1px solid #cbd8c2!important}
.alert.error{background:#f6e5df!important;color:#8d342b!important;border:1px solid #e6c7bc!important}
footer{text-align:center;padding:24px;margin-top:40px;background:var(--bn-wine);color:#e9d9ca;font-size:11px;letter-spacing:1px}
@media(max-width:700px){main{padding:0 16px!important;margin:25px auto!important}table{display:block;overflow-x:auto;white-space:nowrap}}
</style>

<style>
/* Dashboard sirkulasi */
:root{--lb-green:#53664e;--lb-wine2:#552735}
main h1.lb-title{font:400 32px/1.2 'Playfair Display',Georgia,serif!important;margin:0 0 2px!important}
.lb-sub{margin:0;font-size:13px;color:var(--bn-muted)}
.lb-stack{display:flex;flex-direction:column;gap:16px;margin-top:18px}
.lb-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.lb-grid2.lb-wide{grid-template-columns:minmax(0,1fr) minmax(0,1.2fr)}
.lb-panel{background:var(--bn-cream);border:1px solid var(--bn-line);padding:18px 20px;box-shadow:0 10px 28px #3b21140b}
.lb-panel h3{font-size:20px!important;margin:0 0 12px!important}
.lb-panel label{display:block;font:600 11px 'DM Sans',sans-serif!important;color:var(--bn-muted);margin:0 0 5px!important}
.lb-panel select{width:100%;margin-bottom:10px;font-size:13px}
.lb-panel table{box-shadow:none!important;margin:0!important;background:transparent!important}
.lb-panel th{background:transparent!important;color:var(--bn-muted)!important;border-bottom:1px solid var(--bn-line)!important;padding:8px 6px!important}
.lb-panel td{padding:10px 6px!important;font-size:13px!important}
.lb-btn{display:block;width:100%;border:0;padding:12px 16px!important;background:var(--bn-wine)!important;color:#fff7ee!important;font:600 11px 'DM Sans',sans-serif!important;letter-spacing:1px;text-transform:uppercase;cursor:pointer;margin-top:6px}
.lb-btn:hover{opacity:.9}
.lb-btn.lb-green{background:var(--lb-green)!important}
.lb-note{font-size:12px;color:var(--bn-muted);margin:2px 0 0}
.lb-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px}
.lb-stat{padding:16px 18px}
.lb-stat .lb-k{font-size:13px;opacity:.9}
.lb-stat .lb-v{font:400 32px/1.25 'Playfair Display',Georgia,serif}
.lb-stat .lb-s{font-size:12px;opacity:.85}
.lb-s1{background:var(--bn-wine);color:#fff7ee}
.lb-s2{background:var(--lb-green);color:#fff7ee}
.lb-s3{background:var(--bn-gold);color:var(--bn-ink)}
.lb-s4{background:var(--lb-wine2);color:#fff7ee}
.lb-donut{display:flex;align-items:center;justify-content:center;gap:22px;flex-wrap:wrap}
.lb-legend{display:grid;gap:5px;font-size:13px}
.lb-sw{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:8px}
.lb-row{display:grid;grid-template-columns:150px minmax(0,1fr) 40px;gap:10px;align-items:center;font-size:13px;margin-bottom:10px}
.lb-row.lb-inv{grid-template-columns:minmax(160px,300px) minmax(0,1fr) 90px}
.lb-row .lb-n{text-align:right}
.lb-bar{height:8px;background:#eadfd4;border-radius:4px;overflow:hidden}
.lb-bar i{display:block;height:100%;border-radius:4px}
.lb-pill{display:inline-block;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:500;white-space:nowrap}
.lb-ok{background:#e1e9dd;color:#33432f}
.lb-pj{background:#e9ddcb;color:#6b4a2f}
.lb-tl{background:#efd9d3;color:#5a1f2b}
.lb-warn{background:var(--bn-wine);color:#fff7ee}
.lb-link{display:inline-block;margin-top:12px;background:var(--bn-wine);color:#fff7ee!important;text-decoration:none;font-size:11px;letter-spacing:1px;text-transform:uppercase;font-weight:600;padding:10px 16px}
.lb-empty{color:var(--bn-muted);font-size:13px;margin:0}
.lb-diag{background:#f6e5df;border:1px solid #e6c7bc;color:#6b2222;padding:14px 18px;margin:16px 0;font-size:13px}
.lb-diag p{margin:6px 0 0}
.lb-diag code{background:#fffaf3;padding:1px 6px}
@media(max-width:800px){
  .lb-grid2,.lb-grid2.lb-wide{grid-template-columns:1fr}
  .lb-row{grid-template-columns:110px minmax(0,1fr) 34px}
}
</style>
</head>
<body>

<?php $lb_active = 'sirkulasi'; include 'header.php'; ?>

<main>

  <h1 class="lb-title">Sirkulasi</h1>
  <p class="lb-sub">Batas pinjam <?= BATAS_HARI ?> hari · Denda <?= rp(DENDA_PER_HARI) ?>/hari</p>

  <?php if ($diag_masalah): ?>
      <div class="lb-diag">
        <strong>Data belum terbaca dengan benar.</strong>
        <p>Terhubung ke database: <code><?= e($diag_db !== '' ? $diag_db : '(tidak diketahui)') ?></code></p>
        <p>Masalah: <?= e(implode(', ', $diag_masalah)) ?></p>
        <?php if ($diag_lain): ?>
          <p>Tabel yang sama ditemukan di database: <code><?= e(implode(', ', $diag_lain)) ?></code>.
          Kalau datamu ada di sana, ubah <code>$dbname</code> di <code>db_config.php</code> ke nama itu.</p>
        <?php else: ?>
          <p>Belum ada database lain yang berisi tabel ini. Impor file SQL database libs lewat phpMyAdmin
          (tab Import, database <code><?= e($diag_db) ?></code>).</p>
        <?php endif; ?>
      </div>
  <?php endif; ?>

  <?php if ($message): ?>
      <div class="alert success"><?= preg_replace('/\*{1,2}(.+?)\*{1,2}/', '<strong>$1</strong>', $message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
      <div class="alert error"><?= $error ?></div>
  <?php endif; ?>

  <div class="lb-stack">

    <div class="lb-grid2">
      <div class="lb-panel">
        <h3>Peminjaman (Check-Out)</h3>
        <form method="POST">
          <label for="id_anggota">Pilih anggota</label>
          <select name="id_anggota" id="id_anggota" required>
              <option value="">-- Pilih Anggota --</option>
              <?php foreach ($anggota_list as $anggota): ?>
                  <option value="<?= e($anggota['id']) ?>">
                      <?= e($anggota['nama']) ?> (<?= e($anggota['nim_nik']) ?>)
                  </option>
              <?php endforeach; ?>
          </select>

          <label for="id_buku">Pilih judul buku (stok tersedia)</label>
          <select name="id_buku" id="id_buku" required>
              <option value="">-- Pilih Buku --</option>
              <?php foreach ($buku_tersedia_list as $buku_avail): ?>
                  <option value="<?= e($buku_avail['id']) ?>">
                      <?= e($buku_avail['judul']) ?>
                  </option>
              <?php endforeach; ?>
          </select>

          <button type="submit" name="pinjam" class="btn-pinjam lb-btn">Pinjam Buku</button>
        </form>
      </div>

      <div class="lb-panel">
        <h3>Pengembalian (Check-In)</h3>
        <form method="POST">
          <label for="id_sirkulasi">Pilih transaksi aktif</label>
          <select name="id_sirkulasi" id="id_sirkulasi" required>
              <option value="">-- Pilih Transaksi --</option>
              <?php foreach ($active_loans as $loan): ?>
                  <?php 
                      $info_denda = ($loan['denda_saat_ini'] > 0) ? 
                          " (DENDA: " . rp($loan['denda_saat_ini']) . ")" : 
                          "";
                  ?>
                  <option value="<?= e($loan['id_sirkulasi']) ?>">
                      <?= e($loan['judul']) ?> oleh <?= e($loan['nama_anggota']) ?><?= e($info_denda) ?>
                  </option>
              <?php endforeach; ?>
          </select>
          <p class="lb-note">Denda dihitung otomatis bila lewat <?= BATAS_HARI ?> hari.</p>
          <button type="submit" name="kembalikan" class="btn-kembali lb-btn lb-green" style="margin-top:16px">Kembalikan Buku</button>
        </form>
      </div>
    </div>

    <div class="lb-stats">
      <div class="lb-stat lb-s1"><div class="lb-k">Judul koleksi</div><div class="lb-v"><?= number_format($jumlah_judul, 0, ',', '.') ?></div><div class="lb-s"><?= number_format($total_eksemplar, 0, ',', '.') ?> eksemplar</div></div>
      <div class="lb-stat lb-s2"><div class="lb-k">Eksemplar tersedia</div><div class="lb-v"><?= number_format($total_tersedia, 0, ',', '.') ?></div><div class="lb-s">dari <?= number_format($total_eksemplar, 0, ',', '.') ?></div></div>
      <div class="lb-stat lb-s3"><div class="lb-k">Sedang dipinjam</div><div class="lb-v"><?= number_format($aktif_total, 0, ',', '.') ?></div><div class="lb-s">transaksi aktif</div></div>
      <div class="lb-stat lb-s4"><div class="lb-k">Terlambat</div><div class="lb-v"><?= number_format($aktif_telat, 0, ',', '.') ?></div><div class="lb-s">denda berjalan <?= rp($denda_berjalan) ?></div></div>
    </div>

    <div class="lb-grid2 lb-wide">
      <div class="lb-panel">
        <h3>Status transaksi</h3>
        <div class="lb-donut">
          <?php $R = 40; $C = 2 * M_PI * $R; $acc = 0; ?>
          <svg viewBox="0 0 120 120" width="130" height="130" role="img" aria-label="Donut status transaksi, total <?= (int)$total_transaksi ?>">
            <g fill="none" stroke-width="16" transform="rotate(-90 60 60)">
              <circle cx="60" cy="60" r="<?= $R ?>" stroke="#dfd0c1"/>
              <?php if ($total_transaksi > 0): foreach ($donut as $seg): if ($seg[1] <= 0) continue;
                    $len = $C * $seg[1] / $total_transaksi; ?>
                <circle cx="60" cy="60" r="<?= $R ?>" stroke="<?= $seg[2] ?>" stroke-dasharray="<?= sprintf('%F', $len) ?> <?= sprintf('%F', $C - $len) ?>" stroke-dashoffset="<?= sprintf('%F', -$acc) ?>"/>
              <?php $acc += $len; endforeach; endif; ?>
            </g>
            <text x="60" y="63" text-anchor="middle" font-size="20" font-weight="500" fill="#302321"><?= (int)$total_transaksi ?></text>
            <text x="60" y="77" text-anchor="middle" font-size="10" fill="#82736a">transaksi</text>
          </svg>
          <div class="lb-legend">
            <?php foreach ($donut as $seg): ?>
              <div><span class="lb-sw" style="background:<?= $seg[2] ?>"></span><?= e($seg[0]) ?> · <?= (int)$seg[1] ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="lb-panel">
        <h3>Judul paling sering dipinjam</h3>
        <?php if ($populer): foreach ($populer as $p): ?>
          <div class="lb-row">
            <span><?= e($p['judul']) ?></span>
            <div class="lb-bar"><i style="width:<?= round(100 * $p['jml'] / $populer_max) ?>%;background:var(--bn-wine)"></i></div>
            <span class="lb-n"><?= (int)$p['jml'] ?></span>
          </div>
        <?php endforeach; else: ?>
          <p class="lb-empty">Belum ada transaksi peminjaman.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="lb-panel">
      <h3>Peminjaman aktif</h3>
      <?php if (!empty($active_loans)): ?>
        <table>
          <thead>
            <tr><th>Judul buku</th><th>Peminjam</th><th>Tgl. pinjam</th><th>Target kembali</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($active_loans as $loan): ?>
              <tr>
                <td><?= e($loan['judul']) ?></td>
                <td><?= e($loan['nama_anggota']) ?></td>
                <td><?= tgl($loan['tgl_pinjam']) ?></td>
                <td><?= tgl($loan['tgl_kembali_target']) ?></td>
                <td>
                  <?php if ($loan['denda_saat_ini'] > 0): ?>
                    <span class="lb-pill lb-warn">Terlambat · <?= rp($loan['denda_saat_ini']) ?></span>
                  <?php else: ?>
                    <span class="lb-pill lb-ok">Tepat waktu</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="lb-empty">Tidak ada transaksi peminjaman yang sedang aktif.</p>
      <?php endif; ?>
    </div>

    <div class="lb-panel" id="riwayat">
      <h3>Riwayat transaksi</h3>
      <?php if (!empty($riwayat)): ?>
        <table>
          <thead>
            <tr><th>Judul buku</th><th>Peminjam</th><th>Tgl. pinjam</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($riwayat as $t): ?>
              <tr>
                <td><?= e($t['judul']) ?></td>
                <td><?= e($t['nama_anggota']) ?></td>
                <td><?= tgl($t['tgl_pinjam']) ?></td>
                <td>
                  <?php
                    if ($t['tgl_kembali_aktual'] === null) {
                        $d = hitungDenda($t['tgl_pinjam']);
                        if ($d > 0) { echo '<span class="lb-pill lb-warn">Terlambat · ' . rp($d) . '</span>'; }
                        else { echo '<span class="lb-pill lb-pj">Dipinjam</span>'; }
                    } elseif ((int)$t['denda'] > 0) {
                        echo '<span class="lb-pill lb-tl">Kembali terlambat · ' . rp($t['denda']) . '</span>';
                    } else {
                        echo '<span class="lb-pill lb-ok">Kembali</span>';
                    }
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if ($lihat_semua): ?>
          <a class="lb-link" href="Sirkulasi.php#riwayat">Tampilkan 5 terbaru</a>
        <?php elseif ($total_transaksi > 5): ?>
          <a class="lb-link" href="?semua=1#riwayat">Lihat semua transaksi</a>
        <?php endif; ?>
      <?php else: ?>
        <p class="lb-empty">Belum ada transaksi.</p>
      <?php endif; ?>
    </div>

    <div class="lb-panel">
      <h3>Inventaris koleksi</h3>
      <?php if (!empty($all_books)): foreach ($all_books as $book):
            $st = (int)$book['stok_total'];
            $sv = (int)$book['stok_tersedia'];
            $pct = $st > 0 ? round(100 * $sv / $st, 1) : 0;
      ?>
        <div class="lb-row lb-inv">
          <span><?= e($book['judul']) ?> <span style="color:var(--bn-muted);font-size:12px">· <?= e($book['pengarang']) ?>, <?= e($book['tahun']) ?></span></span>
          <div class="lb-bar"><i style="width:<?= $pct ?>%;background:var(--lb-green)"></i></div>
          <span class="lb-n" style="<?= $sv == 0 ? 'color:var(--bn-wine);font-weight:600' : '' ?>"><?= $sv ?> / <?= $st ?></span>
        </div>
      <?php endforeach; else: ?>
        <p class="lb-empty">Katalog bibliografi masih kosong. Silakan tambahkan buku melalui halaman Bibliografi.</p>
      <?php endif; ?>
    </div>

  </div>
</main>

<footer>
  © 2025 libs — Perpustakaan Digital
</footer>

</body>
</html>