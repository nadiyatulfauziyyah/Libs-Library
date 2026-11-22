<?php
// File: save_bibliografi.php
// KOREKSI: MENGGUNAKAN NAMA TABEL tbl_blibliografi

// Sertakan file koneksi
include 'db_config.php';

// Set header response ke JSON
header('Content-Type: application/json');

// Ambil data JSON yang dikirim melalui AJAX
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Jika bukan JSON (mis. form dengan upload cover memakai multipart/form-data), pakai $_POST
if (!is_array($data)) {
    $data = $_POST;
}

// Ambil mode dari query string (?mode=create atau ?mode=update)
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';

// Inisialisasi respons
$response = array(
    'success' => false,
    'message' => ''
);

// =====================================================================================
// FITUR COVER BUKU
// =====================================================================================
define('COVER_DIR', __DIR__ . '/uploads/cover/');
define('COVER_MAKS', 2 * 1024 * 1024); // 2 MB

// Validasi file cover SEBELUM data disimpan. Mengembalikan pesan error atau null.
function validasiCover() {
    if (!isset($_FILES['cover']) || $_FILES['cover']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES['cover'];
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        return 'Ukuran cover melebihi batas server.';
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return 'Upload cover gagal (kode ' . $f['error'] . ').';
    }
    if ($f['size'] > COVER_MAKS) {
        return 'Ukuran cover maksimal 2 MB.';
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($info['mime'], array('image/jpeg', 'image/png', 'image/webp'))) {
        return 'Cover harus berupa gambar JPG, PNG, atau WEBP.';
    }
    return null;
}

function hapusFileCover($nama) {
    if ($nama && basename($nama) === $nama && is_file(COVER_DIR . $nama)) {
        @unlink(COVER_DIR . $nama);
    }
}

// Simpan / ganti / hapus cover untuk bibliografi $id. Mengembalikan pesan error atau null.
function simpanCover($conn, $id, $hapus) {
    $q = $conn->prepare("SELECT cover FROM tbl_blibliografi WHERE id = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    $lama = $row ? $row['cover'] : null;

    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $ekstensi = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
        $info = getimagesize($_FILES['cover']['tmp_name']);
        $nama = 'cover_' . $id . '_' . time() . '.' . $ekstensi[$info['mime']];

        if (!is_dir(COVER_DIR) && !mkdir(COVER_DIR, 0777, true)) {
            return 'Folder uploads/cover tidak bisa dibuat.';
        }
        if (!move_uploaded_file($_FILES['cover']['tmp_name'], COVER_DIR . $nama)) {
            return 'Gagal menyimpan file cover.';
        }

        $u = $conn->prepare("UPDATE tbl_blibliografi SET cover = ? WHERE id = ?");
        $u->bind_param("si", $nama, $id);
        $u->execute();
        $u->close();
        hapusFileCover($lama);

    } elseif ($hapus) {
        $u = $conn->prepare("UPDATE tbl_blibliografi SET cover = NULL WHERE id = ?");
        $u->bind_param("i", $id);
        $u->execute();
        $u->close();
        hapusFileCover($lama);
    }
    return null;
}

// --- LOGIKA UTAMA ---

if ($mode == 'create' || $mode == 'update') {

    // 1. Ambil dan sanitasi data umum
    $judul = isset($data['judul']) ? $data['judul'] : '';

    $pengarang = isset($data['pengarang'])
        ? $data['pengarang']
        : '';

    $kategori = isset($data['kategori'])
        ? $data['kategori']
        : '';

    $tahun = (int)(isset($data['tahun']) ? $data['tahun'] : 0);

    // Validasi dasar
    if (empty($judul) || empty($pengarang) || $tahun == 0) {
        $response['message'] = "Judul, Pengarang, dan Tahun tidak boleh kosong.";

        echo json_encode($response);
        $conn->close();
        exit;
    }

    // Validasi cover (jika ada) sebelum menyimpan apa pun
    $errCover = validasiCover();
    if ($errCover) {
        $response['message'] = $errCover;

        echo json_encode($response);
        $conn->close();
        exit;
    }

    if ($mode == 'create') {

        // --- MODE CREATE (INSERT tbl_blibliografi) ---
        $stmt = $conn->prepare(
            "INSERT INTO tbl_blibliografi
            (judul, pengarang, kategori, tahun)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssi",
            $judul,
            $pengarang,
            $kategori,
            $tahun
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Data baru berhasil ditambahkan.";
            $response['new_id'] = $conn->insert_id;

            // Simpan cover (jika ada)
            $errSimpan = simpanCover($conn, $response['new_id'], false);
            if ($errSimpan) {
                $response['message'] = "Data tersimpan, tetapi cover gagal: " . $errSimpan;
            }
        } else {
            $response['message'] = "Error saat menambahkan data: " . $stmt->error;
        }

        $stmt->close();

    } elseif ($mode == 'update') {

        // --- MODE UPDATE ---
        $id = (int)(isset($data['id']) ? $data['id'] : 0);

        // PENGECEKAN ID UPDATE
        if ($id <= 0) {
            $response['message'] = "ERROR: ID buku tidak valid untuk update. Pastikan ID dikirim dengan benar dari client.";

            echo json_encode($response);
            $conn->close();
            exit;
        }

        $stmt = $conn->prepare(
            "UPDATE tbl_blibliografi
            SET judul = ?, pengarang = ?, kategori = ?, tahun = ?
            WHERE id = ?"
        );

        $stmt->bind_param(
            "sssii",
            $judul,
            $pengarang,
            $kategori,
            $tahun,
            $id
        );

        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0 || $conn->errno == 0) {
                $response['success'] = true;
                $response['message'] = "Data berhasil diperbarui (ID: {$id}).";
            } else {
                $response['success'] = true;
                $response['message'] = "Data berhasil diperbarui (atau tidak ada perubahan data).";
            }

            // Ganti / hapus cover (jika diminta)
            $hapusCover = !empty($data['hapus_cover']) && $data['hapus_cover'] !== '0';
            $errSimpan = simpanCover($conn, $id, $hapusCover);
            if ($errSimpan) {
                $response['message'] = "Data diperbarui, tetapi cover gagal: " . $errSimpan;
            }

        } else {
            $response['message'] = "Error saat memperbarui data: " . $stmt->error;
        }

        $stmt->close();
    }

} elseif ($mode == 'delete') {

    // --- MODE DELETE (DELETE tbl_blibliografi) ---
    $id = (int)(isset($data['id']) ? $data['id'] : 0);

    if ($id <= 0) {
        $response['message'] = "ID buku tidak valid untuk penghapusan.";

        echo json_encode($response);
        $conn->close();
        exit;
    }

    // Catat nama file cover agar bisa ikut dihapus
    $qc = $conn->prepare("SELECT cover FROM tbl_blibliografi WHERE id = ?");
    $qc->bind_param("i", $id);
    $qc->execute();
    $rc = $qc->get_result()->fetch_assoc();
    $qc->close();
    $fileCover = $rc ? $rc['cover'] : null;

    $stmt = $conn->prepare(
        "DELETE FROM tbl_blibliografi WHERE id = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        if ($stmt->affected_rows > 0) {
            hapusFileCover($fileCover);
            $response['success'] = true;
            $response['message'] = "Data ID {$id} berhasil dihapus.";
        } else {
            $response['message'] = "Gagal menghapus. ID {$id} tidak ditemukan.";
        }

    } else {
        $response['message'] = "Error saat menghapus data: " . $stmt->error;
    }

    $stmt->close();

}

// =====================================================================================
// LOGIKA STOK BARU (TIDAK BERUBAH)
// =====================================================================================

elseif ($mode == 'create_stok' || $mode == 'update_stok') {

    $id_biblio = (int)(
        isset($data['id_biblio']) ? $data['id_biblio'] : 0
    );

    $id_buku = (int)(
        isset($data['id_buku']) ? $data['id_buku'] : 0
    );

    $stok_total = (int)(
        isset($data['stok_total']) ? $data['stok_total'] : 0
    );

    $stok_tersedia = (int)(
        isset($data['stok_tersedia']) ? $data['stok_tersedia'] : 0
    );

    if ($id_biblio <= 0 || $stok_total < 0) {
        $response['message'] = "Data input stok tidak valid.";

        echo json_encode($response);
        $conn->close();
        exit;
    }

    if ($mode === 'create_stok') {

        // --- INSERT ke tbl_buku ---
        // Pengecekan stok dilakukan sebelum CREATE oleh proses sebelumnya.

        $stmt = $conn->prepare(
            "INSERT INTO tbl_buku
            (id_bibliografi, stok_total, stok_tersedia)
            VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "iii",
            $id_biblio,
            $stok_total,
            $stok_tersedia
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Stok baru berhasil ditambahkan ke tbl_buku.';
        } else {
            $response['message'] = 'Error saat menambah stok: ' . $stmt->error;
        }

        $stmt->close();

    } elseif ($mode === 'update_stok') {

        // --- UPDATE tbl_buku ---
        // Jika id_buku tidak dikirim, update berdasarkan id_bibliografi

        if ($id_buku <= 0) {

            $stmt = $conn->prepare(
                "UPDATE tbl_buku
                SET stok_total = ?, stok_tersedia = ?
                WHERE id_bibliografi = ?"
            );

            $stmt->bind_param(
                "iii",
                $stok_total,
                $stok_tersedia,
                $id_biblio
            );

        } else {

            // Update spesifik berdasarkan ID buku fisik
            $stmt = $conn->prepare(
                "UPDATE tbl_buku
                SET stok_total = ?, stok_tersedia = ?
                WHERE id = ?"
            );

            $stmt->bind_param(
                "iii",
                $stok_total,
                $stok_tersedia,
                $id_buku
            );
        }

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Stok berhasil diperbarui di tbl_buku.';
        } else {
            $response['message'] = 'Error saat update stok: ' . $stmt->error;
        }

        $stmt->close();
    }

}

// =====================================================================================

else {
    $response['message'] = "Mode operasi tidak dikenali.";
}

// Output hasil
echo json_encode($response);

// Tutup koneksi database
$conn->close();
?>
