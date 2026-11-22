<?php
// File: bibliografi.php (Halaman Daftar Bibliografi dengan Stok)

session_start();
// Pastikan user sudah login, jika tidak, arahkan ke login.php
if (!isset($_SESSION["user_nama"])) {
    header("Location: login.php");
    exit;
}

include 'db_config.php'; 

// Kelas besar klasifikasi (DDC) untuk kategori
$DDC_KELAS = array(
    '000 - Karya Umum',
    '100 - Filsafat & Psikologi',
    '200 - Agama',
    '300 - Ilmu Sosial',
    '400 - Bahasa',
    '500 - Sains Murni',
    '600 - Teknologi',
    '700 - Seni & Olahraga',
    '800 - Sastra',
    '900 - Sejarah & Geografi'
);

// ===== FITUR TAMBAHAN (satu file): ekspor CSV & cetak label barcode =====
function code128_svg($text, $tinggi = 50) {
    static $P = null;
    if ($P === null) {
        $P = explode(' ', '212222 222122 222221 121223 121322 131222 122213 122312 132212 221213 221312 231212 112232 122132 122231 113222 123122 123221 223211 221132 221231 213212 223112 312131 311222 321122 321221 312212 322112 322211 212123 212321 232121 111323 131123 131321 112313 132113 132311 211313 231113 231311 112133 112331 132131 113123 113321 133121 313121 211331 231131 213113 213311 213131 311123 311321 331121 312113 312311 332111 314111 221411 431111 111224 111422 121124 121421 141122 141221 112214 112412 122114 122411 142112 142211 241211 221114 413111 241112 134111 111242 121142 121241 114212 124112 124211 411212 421112 421211 212141 214121 412121 111143 111341 131141 114113 114311 411113 411311 113141 114131 311141 411131 211412 211214 211232 2331112');
    }
    $nilai = array();
    $jumlah = 104; // Start Code B
    for ($i = 0; $i < strlen($text); $i++) {
        $v = ord($text[$i]) - 32;
        $nilai[] = $v;
        $jumlah += $v * ($i + 1);
    }
    $urutan = array_merge(array(104), $nilai, array($jumlah % 103, 106));
    $x = 10; $rects = '';
    foreach ($urutan as $v) {
        $lebar = str_split($P[$v]);
        foreach ($lebar as $i => $w) {
            if ($i % 2 == 0) { $rects .= "<rect x='$x' y='0' width='$w' height='$tinggi'/>"; }
            $x += (int)$w;
        }
    }
    $total = $x + 10;
    return "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 $total $tinggi' preserveAspectRatio='none' class='bc'><g fill='#000'>$rects</g></svg>";
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

if ($aksi == 'export') {
    $rs = $conn->query("SELECT bg.id, bg.judul, bg.pengarang, bg.kategori, bg.tahun,
            COALESCE(b.stok_total,0) AS stok_total, COALESCE(b.stok_tersedia,0) AS stok_tersedia
            FROM tbl_blibliografi bg LEFT JOIN tbl_buku b ON bg.id = b.id_bibliografi ORDER BY bg.id ASC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="katalog_bibliografi_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF";          // BOM agar huruf/UTF-8 terbaca benar
    echo "sep=;\r\n";               // memaksa Excel memecah kolom dengan titik koma
    $out = fopen('php://output', 'w');
    fputcsv($out, array('ID', 'Judul', 'Pengarang', 'Kategori', 'Tahun', 'Stok Total', 'Stok Tersedia'), ';');
    while ($r = $rs->fetch_assoc()) {
        fputcsv($out, array($r['id'], $r['judul'], $r['pengarang'], $r['kategori'], $r['tahun'], $r['stok_total'], $r['stok_tersedia']), ';');
    }
    fclose($out);
    exit;
}

if ($aksi == 'cetak') {
    $ids = array_filter(array_map('intval', explode(',', isset($_GET['ids']) ? $_GET['ids'] : '')));
    $salinan = isset($_GET['salinan']) ? max(1, min(50, (int)$_GET['salinan'])) : 1;
    if (!$ids) { echo "Tidak ada buku yang dipilih."; exit; }
    $rs = $conn->query("SELECT id, judul, pengarang FROM tbl_blibliografi WHERE id IN (" . implode(',', $ids) . ") ORDER BY id ASC");
    $h = function($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
    ?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Label Barcode Koleksi — Libs</title>
<style>
body{font-family:Arial,sans-serif;margin:20px;color:#222}
.bar{margin-bottom:16px}.bar button{padding:8px 14px;cursor:pointer;margin-right:6px}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.label{border:1px dashed #999;padding:8px;text-align:center;page-break-inside:avoid}
.judul{font-size:12px;font-weight:bold;line-height:1.2;max-height:2.4em;overflow:hidden}
.pengarang{font-size:10px;color:#555;margin-bottom:4px}
.bc{width:100%;height:50px;display:block}
.kode{font:12px monospace;letter-spacing:2px;margin-top:2px}
@media print{.bar{display:none}body{margin:8mm}}
</style></head><body>
<div class="bar"><button onclick="window.print()">🖨️ Cetak / Simpan PDF</button><button onclick="window.close()">Tutup</button></div>
<div class="grid">
<?php while ($r = $rs->fetch_assoc()):
    $kode = 'BK' . str_pad($r['id'], 5, '0', STR_PAD_LEFT);
    for ($i = 0; $i < $salinan; $i++): ?>
  <div class="label"><div class="judul"><?= $h($r['judul']) ?></div><div class="pengarang"><?= $h($r['pengarang']) ?></div><?= code128_svg($kode) ?><div class="kode"><?= $kode ?></div></div>
<?php endfor; endwhile; ?>
</div></body></html>
<?php
    exit;
}
// ===== AKHIR FITUR TAMBAHAN SERVER-SIDE =====

// --- PERUBAHAN PHP: Menggunakan JOIN ke tbl_buku ---
$sql = "
    SELECT 
        bg.id, 
        bg.judul, 
        bg.pengarang, 
        bg.kategori, 
        bg.tahun,
        bg.cover,
        -- Ambil data stok dari tbl_buku menggunakan LEFT JOIN
        b.id AS id_buku,
        b.stok_total,
        b.stok_tersedia
    FROM tbl_blibliografi bg
    LEFT JOIN tbl_buku b ON bg.id = b.id_bibliografi
    ORDER BY bg.id DESC
"; 
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Bibliografi — Libs</title>
<style>
/* --- STYLE CSS ASLI ANDA --- */
body {font-family: Arial, sans-serif; margin:0; background:#f5f5f5; color:#333;}
main {max-width:1100px; margin:20px auto; padding:0 15px;}
h1 {color:#FA8072;}
table {width:100%; border-collapse:collapse; margin-top:20px; background:white; border-radius:8px; overflow:hidden;}
th, td {padding:10px; border-bottom:1px solid #ddd; text-align:left;}
th {background:#FA8072; color:white;}
tr:hover {background:#ffe6e0;}
button {padding:6px 10px; border:none; border-radius:4px; cursor:pointer; font-weight:bold; margin-right:5px;} /* Margin-right ditambahkan */
.add-btn {background:#34d399; color:white; margin-top:10px;}
.edit-btn {background:#60a5fa; color:white;}
.delete-btn {background:#ef4444; color:white;}
.stock-btn {background:#FFC107; color:#333;} /* Tombol Baru */

/* --- STYLE MODAL BARU --- */
.modal-bg {
    display: none; 
    position: fixed; 
    z-index: 1000; 
    left: 0; 
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto;
    background-color: rgba(0,0,0,0.4); 
    justify-content: center;
    align-items: center;
}
.modal-content {
    background-color: #fefefe;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 400px;
}
.modal-content input {
    width: calc(100% - 20px);
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
}
.modal-content button {
    padding: 10px 15px;
    margin-top: 10px;
}
</style>
<link rel="stylesheet" href="assets/libs.css?v=<?= @filemtime(__DIR__ . '/assets/libs.css') ?>">

<style>
/* libs EDITORIAL ADMIN THEME */
:root{--bn-paper:#f4eadf;--bn-cream:#fffaf3;--bn-ink:#302321;--bn-wine:#401d28;--bn-muted:#82736a;--bn-line:#dfd0c1;--bn-gold:#b58b60}
body{background:var(--bn-paper)!important;color:var(--bn-ink)!important;font-family:'DM Sans',sans-serif!important}
main,.container,.sirkulasi-container{max-width:1280px!important;margin:34px auto!important;padding:0 clamp(18px,4vw,56px)!important}
h1,h2,h3{font-family:'Playfair Display',Georgia,serif!important;color:var(--bn-wine)!important;font-weight:400!important}
button,.add-btn,.edit-btn,.delete-btn,.stock-btn,.action-btn{border-radius:0!important;font-family:'DM Sans',sans-serif!important;letter-spacing:.5px}
input,select,textarea{background:#fffaf3!important;border:1px solid var(--bn-line)!important;border-radius:0!important;color:var(--bn-ink)!important;padding:11px!important}
table{width:100%;background:var(--bn-cream)!important;border-collapse:collapse!important;box-shadow:0 8px 24px #3b21140b!important;border-radius:0!important;overflow:hidden}
th{background:var(--bn-wine)!important;color:#fff7ee!important;font:600 10px 'DM Sans',sans-serif!important;letter-spacing:1px;text-transform:uppercase}
td,th{padding:14px!important;border-bottom:1px solid var(--bn-line)!important;font-size:12px}
tr:hover{background:#f4e9dd!important}
.form-box,.modal-content{background:var(--bn-cream)!important;border:1px solid var(--bn-line)!important;border-radius:0!important;box-shadow:0 10px 28px #3b21140b!important}
.alert{border-radius:0!important;padding:12px 16px!important}
.alert.success{background:#e8eee2!important;color:#40553b!important;border:1px solid #cbd8c2!important}
.alert.error{background:#f6e5df!important;color:#8d342b!important;border:1px solid #e6c7bc!important}
.bn-hero{min-height:250px;padding:42px clamp(22px,6vw,76px);display:flex;align-items:center;background:linear-gradient(90deg,rgba(48,20,29,.97),rgba(48,20,29,.83) 48%,rgba(48,20,29,.12)),url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1600&q=85') center/cover;color:#f8eee4}
.bn-hero .bn-eyebrow{font:600 9px 'DM Sans',sans-serif;letter-spacing:3px;color:#d7bfae;text-transform:uppercase}
.bn-hero h1{font:400 clamp(38px,5vw,58px)/1.04 'Playfair Display',serif!important;color:#fff6ed!important;margin:13px 0 10px!important}
.bn-hero p{font:16px/1.5 'Playfair Display',serif;color:#e6d7cc;margin:0;max-width:520px}
.bn-section-title{display:flex;justify-content:space-between;align-items:end;gap:16px;margin:30px 0 18px}
.bn-section-title h2{font:400 30px 'Playfair Display',serif!important;margin:0!important}
.bn-section-title p{font-size:12px;color:var(--bn-muted);margin:6px 0 0}
@media(max-width:700px){main,.container,.sirkulasi-container{padding:0 16px!important;margin:25px auto!important}.bn-hero{min-height:220px;padding:34px 22px}table{display:block;overflow-x:auto;white-space:nowrap}.form-grid{grid-template-columns:1fr!important}}
@media print{.bn-hero,.bn-section-title{display:none!important}}

.bn-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
.bn-toolbar input[type=search]{flex:1 1 240px;min-width:200px}
.bn-toolbar select{min-width:170px}
.bn-toolbar .bn-count{font-size:12px;color:var(--bn-muted);margin-left:auto}
.bn-toolbar button{margin:0}
.cover-thumb{width:42px;height:60px;object-fit:cover;border:1px solid var(--bn-line);display:block}
.cover-kosong{display:flex;align-items:center;justify-content:center;text-align:center;font-size:9px;color:var(--bn-muted);background:#efe3d6;line-height:1.2}
</style>

</head>
<body>

<?php $lb_active = 'koleksi'; include 'header.php'; ?>
<section class="bn-hero"><div><div class="bn-eyebrow">libs · COLLECTION MANAGEMENT</div><h1>Kelola data<br>koleksi buku.</h1><p>Rawat katalog perpustakaan, perbarui informasi bibliografi, dan pantau ketersediaan koleksi.</p></div></section>

<main>
<div class="bn-section-title"><div><h2>Daftar Bibliografi</h2><p>Kelola metadata buku dan pantau stok koleksi perpustakaan.</p></div><a href="formBibliografi.php"><button class="add-btn">＋ Tambah Buku</button></a></div>

<div class="bn-toolbar">
    <input type="search" id="searchInput" placeholder="Cari judul atau pengarang…" oninput="applyFilter()">
    <select id="kategoriFilter" onchange="applyFilter()"><option value="">Semua kategori</option></select>
    <span id="resultCount" class="bn-count"></span>
</div>
<div class="bn-toolbar">
    <span id="selCount" class="bn-count" style="margin-left:0">0 dipilih</span>
    <label style="font-size:12px">Salinan/buku <input type="number" id="salinan" value="1" min="1" max="50" style="width:70px!important;padding:6px!important"></label>
    <button type="button" class="edit-btn" onclick="cetakTerpilih()">🏷️ Cetak Barcode Terpilih</button>
    <a href="Bibliografi.php?aksi=export"><button type="button" class="stock-btn">⬇ Ekspor CSV</button></a>
</div>

<table id="bibliografiTable">
    <thead>
        <tr>
            <th style="width:30px"><input type="checkbox" id="pilihSemua" title="Pilih semua yang tampil" onchange="pilihSemua(this.checked)"></th>
            <th>Cover</th>
            <th>ID Biblio</th>
            <th>Judul</th>
            <th>Pengarang</th>
            <th>Kategori</th>
            <th>Tahun</th>
            <th>Stok Tersedia</th>             <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Tentukan status stok (Jika id_buku NULL, berarti belum ada stok di tbl_buku)
				  $stok_tersedia = isset($row['stok_tersedia']) ? $row['stok_tersedia'] : '0';
				$stok_total = isset($row['stok_total']) ? $row['stok_total'] : '0';
				$id_buku = isset($row['id_buku']) ? $row['id_buku'] : null;
                $e = function($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
                $judul_js = htmlspecialchars(json_encode($row['judul']), ENT_QUOTES, 'UTF-8');
                echo "<tr data-biblio-id='{$row['id']}' data-kategori='{$e($row['kategori'])}' data-cari='" . $e(mb_strtolower($row['judul'] . ' ' . $row['pengarang'])) . "'>";
                echo "<td><input type='checkbox' class='pilih' value='{$row['id']}' onchange='hitungPilihan()'></td>";
                if (!empty($row['cover'])) {
                    echo "<td><img src='uploads/cover/{$e($row['cover'])}' class='cover-thumb' alt='Cover'></td>";
                } else {
                    echo "<td><div class='cover-thumb cover-kosong'>No<br>cover</div></td>";
                }
                echo "<td>{$row['id']}</td>";
                echo "<td>{$e($row['judul'])}</td>";
                echo "<td>{$e($row['pengarang'])}</td>";
                echo "<td>{$e($row['kategori'])}</td>";
                echo "<td>{$e($row['tahun'])}</td>";
                echo "<td>{$stok_tersedia} / {$stok_total}</td>";
                echo "<td>";
                echo "<a href='formBibliografi.php?id={$row['id']}'><button class='edit-btn'>✏️ Edit</button></a>";
                echo "<button class='stock-btn' onclick='openStockModal({$row['id']}, {$judul_js}, {$stok_total}, {$stok_tersedia}, \"{$id_buku}\")'>📦 Stok</button>";
                echo "<a href='Bibliografi.php?aksi=cetak&ids={$row['id']}' target='_blank'><button class='edit-btn' title='Cetak label barcode'>🏷️</button></a>";
                echo "<button class='delete-btn' onclick='deleteBook({$row['id']}, this)'>❌ Hapus</button>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='9'>Belum ada data buku dalam katalog.</td></tr>";
        }
        ?>
    </tbody>
</table>


</main>

<footer>
&copy; <?= date('Y') ?>
</footer>

<div id="stockModalBg" class="modal-bg" onclick="closeStockModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h2 style="color:#FA8072; margin-top:0;">Update Stok Buku</h2>
        <p id="modalJudul" style="font-weight:bold;"></p>

        <label for="newStokTotal">Total Stok Fisik (Baru):</label>
        <input type="number" id="newStokTotal" min="0" required>
        
        <p style="font-size:12px; color:#888;">
            Stok Tersedia akan disesuaikan otomatis.<br>
            Saat ini, buku yang dipinjam adalah: <span id="dipinjamCount" style="font-weight:bold;">0</span>
        </p>

        <input type="hidden" id="modalBiblioId">
        <input type="hidden" id="modalBukuId">
        <input type="hidden" id="modalDipinjam">
        
        <button onclick="saveStock()" style="background:#4CAF50; color:white;">💾 Simpan Stok</button>
        <button onclick="closeStockModal()" style="background:#ccc; color:#333;">Tutup</button>
    </div>
</div>
<script>
// --- FITUR TAMBAHAN: pencarian, filter kategori, cetak barcode ---
const bibRows = Array.from(document.querySelectorAll('#bibliografiTable tbody tr[data-biblio-id]'));

const KELAS_DDC = <?= json_encode(array_values($DDC_KELAS), JSON_UNESCAPED_UNICODE) ?>;
const BELUM = '__belum';

(function isiKategori() {
    const sel = document.getElementById('kategoriFilter');
    KELAS_DDC.forEach(k => { const o = document.createElement('option'); o.value = k; o.textContent = k; sel.appendChild(o); });
    const o = document.createElement('option'); o.value = BELUM; o.textContent = 'Belum diklasifikasi'; sel.appendChild(o);
    applyFilter();
})();

function applyFilter() {
    const q = document.getElementById('searchInput').value.trim().toLowerCase();
    const kat = document.getElementById('kategoriFilter').value;
    let tampil = 0;
    bibRows.forEach(r => {
        const k = r.dataset.kategori;
        const cocokKat = !kat || (kat === BELUM ? !KELAS_DDC.includes(k) : k === kat);
        const cocok = (!q || r.dataset.cari.includes(q)) && cocokKat;
        r.style.display = cocok ? '' : 'none';
        if (cocok) tampil++;
    });
    document.getElementById('resultCount').textContent = tampil + ' dari ' + bibRows.length + ' judul';
}

function pilihTampil() {
    return bibRows.filter(r => r.style.display !== 'none').map(r => r.querySelector('.pilih'));
}
function pilihSemua(centang) {
    pilihTampil().forEach(c => c.checked = centang);
    hitungPilihan();
}
function hitungPilihan() {
    const n = bibRows.filter(r => r.querySelector('.pilih').checked).length;
    document.getElementById('selCount').textContent = n + ' dipilih';
}
function cetakTerpilih() {
    const ids = bibRows.filter(r => r.querySelector('.pilih').checked).map(r => r.dataset.biblioId);
    if (!ids.length) { alert('Centang dulu buku yang ingin dicetak barcode-nya.'); return; }
    const salinan = document.getElementById('salinan').value || 1;
    window.open('Bibliografi.php?aksi=cetak&ids=' + ids.join(',') + '&salinan=' + salinan, '_blank');
}
</script>
<script>
// Variabel global untuk menyimpan jumlah buku yang sedang dipinjam
let currentDipinjam = 0;

// --- FUNGSI BARU UNTUK MODAL STOK ---
function openStockModal(biblioId, judul, stokTotal, stokTersedia, idBuku) {
    const modalBg = document.getElementById("stockModalBg");
    const dipinjam = stokTotal - stokTersedia;

    document.getElementById("modalJudul").innerText = judul;
    document.getElementById("newStokTotal").value = stokTotal;
    document.getElementById("modalBiblioId").value = biblioId;
    document.getElementById("modalBukuId").value = idBuku !== 'null' ? idBuku : ''; // Jika ID Buku null
    document.getElementById("modalDipinjam").value = dipinjam;
    document.getElementById("dipinjamCount").innerText = dipinjam;
    currentDipinjam = dipinjam;
    
    modalBg.style.display = 'flex';
}

function closeStockModal() {
    document.getElementById("stockModalBg").style.display = 'none';
}

function saveStock() {
    const biblioId = document.getElementById("modalBiblioId").value;
    const currentBukuId = document.getElementById("modalBukuId").value;
    const newStokTotal = parseInt(document.getElementById("newStokTotal").value);
    const dipinjam = parseInt(document.getElementById("modalDipinjam").value);
    
    if (isNaN(newStokTotal) || newStokTotal < 0) {
        alert("Total Stok harus berupa angka positif.");
        return;
    }
    
    if (newStokTotal < dipinjam) {
        alert(`❌ Gagal: Total Stok Baru (${newStokTotal}) tidak boleh lebih kecil dari jumlah buku yang sedang dipinjam (${dipinjam}).`);
        return;
    }

    const newStokTersedia = newStokTotal - dipinjam;
    
    // Tentukan mode: 'create' jika tbl_buku belum ada (currentBukuId kosong), 'update' jika sudah ada
    const mode = currentBukuId ? 'update_stok' : 'create_stok';
    
    // Panggil endpoint khusus untuk manajemen stok
    fetch('save_bibliografi.php?mode=' + mode, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            id_biblio: biblioId,
            id_buku: currentBukuId,
            stok_total: newStokTotal,
            stok_tersedia: newStokTersedia 
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(`✅ Stok untuk ${document.getElementById("modalJudul").innerText} berhasil diperbarui menjadi ${newStokTotal} (Tersedia: ${newStokTersedia}).`);
            closeStockModal();
            // Refresh halaman atau update baris (untuk saat ini, refresh lebih mudah dan aman)
            window.location.reload(); 
        } else {
            alert("❌ Gagal menyimpan stok: " + result.message);
        }
    })
    .catch(error => {
        console.error('Kesalahan jaringan:', error);
        alert("Terjadi kesalahan koneksi saat menyimpan stok.");
    });
}


// --- FUNGSI GLOBAL ASLI UNTUK MENGHAPUS DATA VIA AJAX ---
function deleteBook(bookId, buttonElement) {
    if (!confirm(`Apakah Anda yakin ingin menghapus data buku ID ${bookId} secara permanen? Catatan: Penghapusan ini akan menghapus data di tbl_bibliografi DAN tbl_buku.`)) {
        return;
    }

    const row = buttonElement.closest('tr');
    
    // Panggil save_bibliografi.php dengan mode=delete
    fetch('save_bibliografi.php?mode=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: bookId }) 
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(`✅ Data ID ${bookId} berhasil dihapus!`);
            row.remove(); 
        } else {
            alert("❌ Gagal menghapus: " + result.message);
        }
    })
    .catch(error => {
        console.error('Kesalahan jaringan:', error);
        alert("Terjadi kesalahan koneksi saat menghapus data.");
    });
}
</script>

</body>
</html>
<?php
// Tutup koneksi database
$conn->close();
?>