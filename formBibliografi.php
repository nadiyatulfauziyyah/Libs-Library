<?php
// Halaman Bibliografi Interaktif — User bisa insert, edit, hapus

include 'db_config.php'; // PASTIKAN FILE INI ADA DAN KONEKSI BERHASIL

$is_edit_mode = false;
$book_data = ['judul' => '', 'pengarang' => '', 'tahun' => '', 'kategori' => '800 - Sastra', 'id' => 0, 'Cover' => ''];

// 1. Cek apakah ada parameter ID di URL (Mode Edit)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    
    // Query untuk mengambil data buku berdasarkan ID
    $sql = "SELECT id, judul, pengarang, kategori, tahun, Cover FROM tbl_blibliografi WHERE id = $edit_id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $book_data = $result->fetch_assoc();
        $is_edit_mode = true;
    } else {
        // Jika ID tidak valid atau tidak ditemukan
        // Anda bisa mengarahkan user kembali atau menampilkan pesan error
        header("Location: bibliografi.php");
        exit();
    }
}
// Koneksi ditutup nanti, atau biarkan terbuka jika Anda membutuhkannya di bagian lain
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $is_edit_mode ? 'Edit' : 'Input' ?> Input Bibliografi — Libs</title>
    <style>
    body {font-family: Arial, sans-serif; margin:0; background:#f5f5f5; color:#333;}
    main {max-width:1000px; margin:20px auto; padding:0 15px;}
    h1 {color:#FA8072;}
    table {width:100%; border-collapse:collapse; margin-top:20px; background:white; border-radius:8px; overflow:hidden;}
    th, td {padding:10px; border-bottom:1px solid #ddd; text-align:left;}
    th {background:#FA8072; color:white;}
    tr:hover {background:#ffe6e0;}
    button {padding:6px 10px; border:none; border-radius:4px; cursor:pointer; font-weight:bold;}
    .add-btn {background:#34d399; color:white; margin-top:10px;}
    .edit-btn {background:#60a5fa; color:white; margin-right:5px;}
    .delete-btn {background:#ef4444; color:white;}
    input[type="text"], input[type="number"] {width:100%; padding:6px; border:1px solid #ccc; border-radius:4px;}
    footer {text-align:center; margin-top:30px; color:#777; font-size:14px;}
    </style>
<link rel="stylesheet" href="assets/libs.css?v=<?= @filemtime(__DIR__ . '/assets/libs.css') ?>">
</head>
<body>

<?php $lb_active = 'koleksi'; include 'header.php'; ?>

<div class="container">
    <h1 id="formTitle"><?= $is_edit_mode ? '✏️ Edit Data Koleksi' : '➕ Input Data Koleksi Baru' ?> (Bibliografi)</h1>
    <p style="color:#475569;font-size:15px">
        <?= $is_edit_mode ? 'Perbarui informasi buku dengan ID: ' . $book_data['id'] : 'Tambahkan informasi lengkap mengenai buku baru ke dalam katalog perpustakaan.' ?>
    </p>

    <div class="section-title">Detail Bibliografi</div>
    <form id="bibliografiForm">
        <input type="hidden" id="book_id" value="<?= $book_data['id'] ?>"> 
    
        <div class="form-group">
            <label for="judul">Judul Buku</label>
            <input type="text" id="judul" required value="<?= htmlspecialchars($book_data['judul']) ?>">
        </div>
      
        <div class="form-group">
            <label for="pengarang">Pengarang / Pencipta</label>
            <input type="text" id="pengarang" required value="<?= htmlspecialchars($book_data['pengarang']) ?>">
        </div>
      
        <div class="form-group">
            <label for="tahun_terbit">Tahun Terbit</label>
            <input type="number" id="tahun" maxlength="4" required value="<?= htmlspecialchars($book_data['tahun']) ?>">
        </div>
      
        <div class="form-group">
            <label for="kategori">Kategori</label>
            <select id="kategori">
                <?php
                $kategori_options = array(
                    '000 - Karya Umum', '100 - Filsafat & Psikologi', '200 - Agama', '300 - Ilmu Sosial',
                    '400 - Bahasa', '500 - Sains Murni', '600 - Teknologi', '700 - Seni & Olahraga',
                    '800 - Sastra', '900 - Sejarah & Geografi'
                );
                // Jika data lama memakai kategori di luar daftar DDC, tampilkan agar tidak berubah diam-diam
                if ($is_edit_mode && !in_array($book_data['kategori'], $kategori_options)) {
                    array_unshift($kategori_options, $book_data['kategori']);
                }
                foreach ($kategori_options as $opt) {
                    $selected = ($opt == $book_data['kategori']) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($opt) . "\" $selected>" . htmlspecialchars($opt) . '</option>';
                }
                ?>
            </select>
        </div> 
      
        <div class="form-group">
            <label for="cover">Cover Buku (opsional — JPG/PNG/WEBP, maks 2 MB)</label>
            <?php if (!empty($book_data['Cover'])): ?>
                <div id="coverSaatIni" style="margin:6px 0">
                    <img src="uploads/cover/<?= htmlspecialchars($book_data['Cover']) ?>" alt="Cover saat ini" style="height:130px;border:1px solid #ccc">
                    <label style="display:block;font-size:13px;margin-top:4px"><input type="checkbox" id="hapus_cover"> Hapus cover ini</label>
                </div>
            <?php endif; ?>
            <input type="file" id="cover" accept="image/jpeg,image/png,image/webp" onchange="previewCover(this)">
            <img id="coverPreview" alt="Pratinjau cover" style="display:none;height:130px;margin-top:8px;border:1px solid #ccc">
        </div>

        <button type="button" onclick="submitBibliografi()">💾 Simpan Data</button>
        <button type="reset" style="background: #64748b; margin-left:10px">Bersihkan Form</button>
    </form>
</div>

<script>
    function previewCover(input) {
        const img = document.getElementById('coverPreview');
        const file = input.files[0];
        if (!file) { img.style.display = 'none'; return; }
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran cover maksimal 2 MB.');
            input.value = ''; img.style.display = 'none'; return;
        }
        img.src = URL.createObjectURL(file);
        img.style.display = 'block';
    }

    function submitBibliografi() {
        const bookId = document.getElementById('book_id').value; // 0 jika baru
        const judul = document.getElementById('judul').value.trim();
        const pengarang = document.getElementById('pengarang').value.trim();
        const tahun = document.getElementById('tahun').value.trim();

        if (!judul || !pengarang || !tahun) {
          alert("Judul, Pengarang, dan Tahun Terbit wajib diisi!");
          return;
        }

        // FormData supaya file cover bisa ikut terkirim
        const fd = new FormData();
        fd.append('id', bookId);
        fd.append('judul', judul);
        fd.append('pengarang', pengarang);
        fd.append('tahun', tahun);
        fd.append('kategori', document.getElementById('kategori').value);
        const fileCover = document.getElementById('cover').files[0];
        if (fileCover) fd.append('cover', fileCover);
        const hapus = document.getElementById('hapus_cover');
        if (hapus && hapus.checked) fd.append('hapus_cover', '1');

        const mode = (bookId > 0) ? 'update' : 'create';

        fetch('save_bibliografi.php?mode=' + mode, { method: 'POST', body: fd })
        .then(response => {
          if (!response.ok) throw new Error('Server mengembalikan error HTTP: ' + response.status);
          return response.json();
        })
        .then(result => {
          if (result.success) {
            if (mode === 'create') {
                alert('✅ ' + result.message + '\nID Baru: ' + result.new_id);
                document.getElementById('bibliografiForm').reset();
                document.getElementById('coverPreview').style.display = 'none';
            } else {
                alert('✅ ' + result.message);
                window.location.href = 'Bibliografi.php';
            }
          } else {
            alert("❌ Gagal menyimpan data: " + result.message);
          }
        })
        .catch(error => {
          console.error('Error saat mengirim data:', error);
          alert("Terjadi kesalahan jaringan/server. Cek konsol browser untuk detail.");
        });
    }
</script>

</body>
</html>