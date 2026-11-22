<?php
session_start();
require_once 'db_config.php';

// ==========================================
// CEK LOGIN
// ==========================================

if (!isset($_SESSION['user_nama'])) {
    header("Location: login.php");
    exit;
}

$error = "";
$message = "";

// ==========================================
// FUNGSI KEAMANAN OUTPUT
// ==========================================

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


// ==========================================
// TAMBAH ANGGOTA
// ==========================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {

    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $nim = isset($_POST['nim_nik']) ? trim($_POST['nim_nik']) : '';
    $prodi = isset($_POST['program_studi']) ? trim($_POST['program_studi']) : '';
    $telp = isset($_POST['no_telp']) ? trim($_POST['no_telp']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($nama == '' || $nim == '' || $prodi == '' ||
        $email == '' || $password == '') {

        $error = "Mohon lengkapi seluruh data wajib.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM tbl_anggota
             WHERE email = ? OR nim_nik = ?"
        );

        $check->bind_param("ss", $email, $nim);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $error = "Email atau ID Anggota sudah terdaftar.";

        } else {

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO tbl_anggota
                (nama, nim_nik, program_studi, no_telp, email, password_hash)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssss",
                $nama,
                $nim,
                $prodi,
                $telp,
                $email,
                $password_hash
            );

            if ($stmt->execute()) {

                $stmt->close();
                $check->close();

                header("Location: Keanggotaan.php?status=added");
                exit;

            } else {

                $error = "Gagal menambahkan anggota: " . $stmt->error;
            }

            $stmt->close();
        }

        $check->close();
    }
}


// ==========================================
// UPDATE ANGGOTA
// ==========================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $nim = isset($_POST['nim_nik']) ? trim($_POST['nim_nik']) : '';
    $prodi = isset($_POST['program_studi']) ? trim($_POST['program_studi']) : '';
    $telp = isset($_POST['no_telp']) ? trim($_POST['no_telp']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($id <= 0 || $nama == '' || $nim == '' ||
        $prodi == '' || $email == '') {

        $error = "Mohon lengkapi seluruh data wajib.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif ($password != '' && strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM tbl_anggota
             WHERE (email = ? OR nim_nik = ?)
             AND id != ?"
        );

        $check->bind_param("ssi", $email, $nim, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $error = "Email atau ID Anggota sudah digunakan anggota lain.";

        } else {

            if ($password != '') {

                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "UPDATE tbl_anggota
                     SET nama = ?,
                         nim_nik = ?,
                         program_studi = ?,
                         no_telp = ?,
                         email = ?,
                         password_hash = ?
                     WHERE id = ?"
                );

                $stmt->bind_param(
                    "ssssssi",
                    $nama,
                    $nim,
                    $prodi,
                    $telp,
                    $email,
                    $password_hash,
                    $id
                );

            } else {

                $stmt = $conn->prepare(
                    "UPDATE tbl_anggota
                     SET nama = ?,
                         nim_nik = ?,
                         program_studi = ?,
                         no_telp = ?,
                         email = ?
                     WHERE id = ?"
                );

                $stmt->bind_param(
                    "sssssi",
                    $nama,
                    $nim,
                    $prodi,
                    $telp,
                    $email,
                    $id
                );
            }

            if ($stmt->execute()) {

                $stmt->close();
                $check->close();

                header("Location: Keanggotaan.php?status=updated");
                exit;

            } else {

                $error = "Gagal memperbarui anggota: " . $stmt->error;
            }

            $stmt->close();
        }

        $check->close();
    }
}


// ==========================================
// DELETE ANGGOTA
// ==========================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus'])) {

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {

        $stmt = $conn->prepare(
            "DELETE FROM tbl_anggota WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {

            $stmt->close();

            header("Location: Keanggotaan.php?status=deleted");
            exit;

        } else {

            $error = "Gagal menghapus anggota: " . $stmt->error;
        }

        $stmt->close();

    } else {

        $error = "ID anggota tidak valid.";
    }
}


// ==========================================
// NOTIFIKASI
// ==========================================

$status = isset($_GET['status']) ? $_GET['status'] : '';

if ($status == 'added') {
    $message = "Anggota berhasil ditambahkan!";
} elseif ($status == 'updated') {
    $message = "Data keanggotaan berhasil diperbarui!";
} elseif ($status == 'deleted') {
    $message = "Anggota berhasil dihapus!";
}


// ==========================================
// DATA UNTUK EDIT
// ==========================================

$editMember = null;

if (isset($_GET['edit'])) {

    $id_edit = (int)$_GET['edit'];

    if ($id_edit > 0) {

        $stmt = $conn->prepare(
            "SELECT id, nama, nim_nik, program_studi, no_telp, email
             FROM tbl_anggota
             WHERE id = ?"
        );

        $stmt->bind_param("i", $id_edit);
        $stmt->execute();

        $stmt->bind_result(
            $edit_id,
            $edit_nama,
            $edit_nim,
            $edit_prodi,
            $edit_telp,
            $edit_email
        );

        if ($stmt->fetch()) {

            $editMember = array(
                'id' => $edit_id,
                'nama' => $edit_nama,
                'nim_nik' => $edit_nim,
                'program_studi' => $edit_prodi,
                'no_telp' => $edit_telp,
                'email' => $edit_email
            );

        } else {

            $error = "Data anggota tidak ditemukan.";
        }

        $stmt->close();
    }
}


// ==========================================
// AMBIL SELURUH DATA ANGGOTA
// ==========================================

$members = array();

$sql = "SELECT id, nama, nim_nik, program_studi, no_telp, email
        FROM tbl_anggota
        ORDER BY id ASC";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    $result->free();

} else {

    $error = "Gagal mengambil data anggota: " . $conn->error;
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Libs — Keanggotaan</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="assets/libs.css?v=<?= @filemtime(__DIR__ . '/assets/libs.css') ?>" rel="stylesheet">


<style>


/* ==========================================
   HERO KEANGGOTAAN - MODEL BANNER SIRKULASI
========================================== */

.membership-hero {
    min-height: 250px;
    padding: 42px clamp(22px, 6vw, 76px);
    display: flex;
    align-items: center;
    background:
        linear-gradient(
            90deg,
            rgba(48,20,29,.97),
            rgba(48,20,29,.83) 48%,
            rgba(48,20,29,.12)
        ),
        url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1600&q=85')
        center/cover;
    color: #f8eee4;
    overflow: hidden;
}

.membership-hero-copy {
    padding: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    max-width: 650px;
}

.membership-hero-copy .eyebrow {
    font-size: 9px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #d7bfae;
}

.membership-hero-copy h1 {
    font-size: clamp(38px, 5vw, 58px) !important;
    line-height: 1.04 !important;
    color: #fff6ed !important;
    margin: 13px 0 10px !important;
}

.membership-hero-copy p {
    max-width: 520px;
    color: #e6d7cc;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 16px;
    line-height: 1.5;
    margin: 0;
}

.membership-hero-copy .hero-cta {
    display: inline-block;
    margin-top: 18px;
    background: #f3e9dd;
    color: #33201f;
    text-decoration: none;
    padding: 12px 18px;
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.membership-hero-photo {
    display: none;
}

/* RESPONSIVE */
@media(max-width:700px) {
    .membership-hero {
        min-height: 220px;
        padding: 34px 22px;
        background-position: center;
    }

    .membership-hero-copy h1 {
        font-size: 38px !important;
    }

    .membership-hero-copy p {
        font-size: 14px;
    }
}



/* ==========================================
   WELCOME STRIP
========================================== */

.welcome-strip {
    padding: 18px clamp(22px, 6vw, 72px);
    background: #e8dbcd;
    color: #5b4b43;
    font-size: 12px;
    letter-spacing: .5px;
}


/* ==========================================
   CONTENT SECTION
========================================== */

.membership-section {
    padding: 54px clamp(22px, 6vw, 72px) 62px;
}

.bn-section-title {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 28px;
}

.bn-section-title h2 {
    font-size: 36px !important;
    margin: 0 !important;
}

.bn-section-title p {
    font-size: 12px;
    color: var(--muted);
    margin: 6px 0 0;
}


/* ==========================================
   FORM PANEL
========================================== */

.member-form-panel {
    background: #fffaf4;
    border: 1px solid var(--line);
    padding: 28px;
    margin-bottom: 35px;
}

.member-form-panel h3 {
    font-size: 25px !important;
    margin: 0 0 8px;
}

.member-form-panel > p {
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 23px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px 22px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-group label {
    color: var(--wine);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .3px;
}

.form-group input {
    width: 100%;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 23px;
    flex-wrap: wrap;
}

.btn-cancel {
    display: inline-block;
    padding: 12px 18px;
    background: #e9ddcf;
    color: var(--wine);
    font-size: 11px;
    letter-spacing: 1px;
    text-decoration: none;
}


/* ==========================================
   ALERT
========================================== */

.alert {
    padding: 13px 16px;
    margin-bottom: 20px;
    font-size: 12px;
}

.alert.success {
    background: #e8eee2;
    color: #40553b;
    border: 1px solid #cbd8c2;
}

.alert.error {
    background: #f6e5df;
    color: #8d342b;
    border: 1px solid #e6c7bc;
}


/* ==========================================
   SEARCH
========================================== */

.search-box {
    width: 100%;
    max-width: 350px;
    margin-bottom: 20px;
    font-size: 12px;
}


/* ==========================================
   TABLE
========================================== */

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.member-table {
    width: 100%;
    min-width: 900px;
    background: #fffaf4;
    border-collapse: collapse;
}

.member-table th {
    background: var(--wine);
    color: white;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    white-space: nowrap;
}

.member-table td,
.member-table th {
    padding: 14px;
    border-bottom: 1px solid var(--line);
    text-align: left;
    font-size: 12px;
}

.member-table tr:hover {
    background: #f4e9dd;
}

.empty-data {
    text-align: center !important;
    padding: 30px !important;
    color: var(--muted);
}


/* ==========================================
   ACTION BUTTONS
========================================== */

.action-group {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
}

.action-group form {
    margin: 0;
}

.action-group a {
    text-decoration: none;
}

.btn-edit {
    background: #94734e !important;
    padding: 9px 12px !important;
    font-size: 10px !important;
}

.btn-delete {
    background: #8f3d42 !important;
    padding: 9px 12px !important;
    font-size: 10px !important;
}

.btn-print {
    background: #526d61 !important;
    padding: 9px 12px !important;
    font-size: 10px !important;
}


/* ==========================================
   MEMBER CARD
========================================== */

#cardArea {
    display: none;
    width: 360px;
    min-height: 215px;
    padding: 22px;
    margin: 25px auto;
    background: #fffaf3;
    border: 2px solid #b58b60;
    color: #302321;
}

.card-title {
    text-align: center;
    font-family: 'Playfair Display', serif;
    font-size: 25px;
    letter-spacing: 3px;
    color: #401d28;
}

.card-subtitle {
    text-align: center;
    font-size: 9px;
    letter-spacing: 2px;
    margin-bottom: 15px;
}

.card-details {
    border-top: 1px solid #b58b60;
    padding-top: 10px;
    font-size: 12px;
}

.card-details p {
    margin: 5px 0;
}

.card-footer {
    margin-top: 15px;
    text-align: right;
    font-size: 9px;
    letter-spacing: 1px;
    color: #82736a;
}


/* ==========================================
   RESPONSIVE
========================================== */

@media(max-width:800px) {

    .membership-hero {
        grid-template-columns: 1fr;
    }

    .membership-hero-photo {
        min-height: 330px;
        grid-row: 1;
    }

    .membership-hero-copy {
        padding: 38px 26px;
    }

    .membership-section {
        padding: 35px 22px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .bn-section-title {
        align-items: flex-start;
        flex-direction: column;
    }

}

@media(max-width:420px) {

    .membership-hero-copy h1 {
        font-size: 48px !important;
    }

    .membership-hero-photo {
        min-height: 260px;
    }

    .member-form-panel {
        padding: 18px;
    }

}


/* ==========================================
   PRINT KARTU
========================================== */

@media print {

    body * {
        visibility: hidden !important;
    }

    #cardArea,
    #cardArea * {
        visibility: visible !important;
    }

    #cardArea {
        display: block !important;
        position: absolute;
        top: 20px;
        left: 20px;
        width: 360px;
        box-shadow: none;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

}


</style>

</head>

<body>


<!-- ==========================================
     HEADER
     MENGIKUTI BERANDA.PHP
========================================== -->

<?php $lb_active = 'anggota'; include 'header.php'; ?>


<!-- ==========================================
     HERO SPLIT SCREEN
========================================== -->

<section class="membership-hero no-print">

    <div class="membership-hero-copy">

        <div class="eyebrow">
            A LITTLE SPACE FOR BIG IDEAS
        </div>

        <h1>
    Setiap Pembaca Punya Tempat di Sini.
		</h1>

        <p>
            Kelola keanggotaan, perbarui informasi,
            dan jadilah bagian dari ruang baca libs.
        </p>

        <a class="hero-cta" href="#memberForm">
            KELOLA KEANGGOTAAN &nbsp; →
        </a>

    </div>


    <div class="membership-hero-photo"
         role="img"
         aria-label="Rak buku di ruang baca libs">
    </div>

</section>


<!-- ==========================================
     WELCOME STRIP
========================================== -->

<div class="welcome-strip no-print">

    Selamat datang kembali,
    <?= e($_SESSION['user_nama']); ?>.
    Kelola informasi anggota perpustakaan libs.

</div>



<!-- ==========================================
     MAIN CONTENT
========================================== -->

<main class="membership-section no-print">


    <!-- JUDUL HALAMAN -->

    <div class="bn-section-title">

        <div>

            <h2>Keanggotaan</h2>

            <p>
                Kelola informasi anggota dan data keanggotaan
                perpustakaan libs.
            </p>

        </div>

    </div>


    <!-- NOTIFIKASI -->

    <?php if ($message != ''): ?>

        <div class="alert success">
            <?= e($message); ?>
        </div>

    <?php endif; ?>


    <?php if ($error != ''): ?>

        <div class="alert error">
            <?= e($error); ?>
        </div>

    <?php endif; ?>



    <!-- ======================================
         FORM TAMBAH / EDIT
    ======================================= -->

    <section class="member-form-panel" id="memberForm">

        <h3>

            <?php
            echo ($editMember != null)
                ? 'Edit Keanggotaan'
                : 'Tambah Keanggotaan';
            ?>

        </h3>

        <p>

            <?php
            echo ($editMember != null)
                ? 'Perbarui informasi anggota melalui formulir berikut.'
                : 'Tambahkan anggota baru ke dalam database perpustakaan libs.';
            ?>

        </p>


        <form method="POST" action="Keanggotaan.php">

            <?php if ($editMember != null): ?>

                <input type="hidden"
                       name="id"
                       value="<?= e($editMember['id']); ?>">

            <?php endif; ?>


            <div class="form-grid">


                <!-- NAMA -->

                <div class="form-group">

                    <label>Nama Lengkap *</label>

                    <input
                        type="text"
                        name="nama"
                        placeholder="Masukkan nama lengkap"
                        value="<?php
                            echo ($editMember != null)
                                ? e($editMember['nama'])
                                : '';
                        ?>"
                        required
                    >

                </div>


                <!-- ID ANGGOTA -->

                <div class="form-group">

                    <label>ID Anggota *</label>

                    <input
                        type="text"
                        name="nim_nik"
                        placeholder="Masukkan ID Anggota"
                        value="<?php
                            echo ($editMember != null)
                                ? e($editMember['nim_nik'])
                                : '';
                        ?>"
                        required
                    >

                </div>


                <!-- PROGRAM STUDI -->

                <div class="form-group">

                    <label>Program Studi *</label>

                    <input
                        type="text"
                        name="program_studi"
                        placeholder="Masukkan program studi"
                        value="<?php
                            echo ($editMember != null)
                                ? e($editMember['program_studi'])
                                : '';
                        ?>"
                        required
                    >

                </div>


                <!-- TELEPON -->

                <div class="form-group">

                    <label>No. Telepon</label>

                    <input
                        type="text"
                        name="no_telp"
                        placeholder="Masukkan nomor telepon"
                        value="<?php
                            echo ($editMember != null)
                                ? e($editMember['no_telp'])
                                : '';
                        ?>"
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label>Email *</label>

                    <input
                        type="email"
                        name="email"
                        placeholder="nama@email.com"
                        value="<?php
                            echo ($editMember != null)
                                ? e($editMember['email'])
                                : '';
                        ?>"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label>

                        Password

                        <?php
                        echo ($editMember != null)
                            ? '(Kosongkan jika tidak ingin mengubah)'
                            : '*';
                        ?>

                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="<?php
                            echo ($editMember != null)
                                ? 'Password baru (opsional)'
                                : 'Minimal 6 karakter';
                        ?>"
                        minlength="6"
                        <?php
                        echo ($editMember == null)
                            ? 'required'
                            : '';
                        ?>
                    >

                </div>


            </div>


            <!-- TOMBOL -->

            <div class="form-actions">

                <?php if ($editMember != null): ?>

                    <button type="submit" name="update">
                        SIMPAN PERUBAHAN
                    </button>

                    <a href="Keanggotaan.php" class="btn-cancel">
                        BATAL
                    </a>

                <?php else: ?>

                    <button type="submit" name="tambah">
                        + TAMBAHKAN ANGGOTA
                    </button>

                <?php endif; ?>

            </div>

        </form>

    </section>



    <!-- ======================================
         DAFTAR KEANGGOTAAN
    ======================================= -->

    <section class="member-list">


        <div class="bn-section-title">

            <div>

                <h2>Daftar Anggota</h2>

                <p>
                    Total anggota terdaftar:
                    <strong><?= count($members); ?></strong>
                </p>

            </div>

        </div>


        <!-- PENCARIAN -->

        <input
            type="text"
            id="searchMember"
            class="search-box"
            placeholder="Cari nama, ID anggota, atau email..."
            onkeyup="searchMembers()"
        >


        <!-- TABEL -->

        <div class="table-wrapper">

            <table class="member-table" id="memberTable">

                <thead>

                    <tr>

                        <th>NO</th>
                        <th>Nama</th>
                        <th>ID Anggota</th>
                        <th>Program Studi</th>
                        <th>No. Telepon</th>
                        <th>Email</th>
                        <th>Aksi</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($members) > 0): ?>

                    <?php $nomor = 1; // Nomor urut tampilan, bukan ID database ?>

                    <?php foreach ($members as $member): ?>

                        <tr>

                            <td>
                                <?= $nomor++; ?>
                            </td>

                            <td>
                                <?= e($member['nama']); ?>
                            </td>

                            <td>
                                <?= e($member['nim_nik']); ?>
                            </td>

                            <td>
                                <?= e($member['program_studi']); ?>
                            </td>

                            <td>
                                <?= e($member['no_telp']); ?>
                            </td>

                            <td>
                                <?= e($member['email']); ?>
                            </td>


                            <!-- AKSI -->

                            <td>

                                <div class="action-group">


                                    <!-- EDIT -->

                                    <a
                                        href="Keanggotaan.php?edit=<?= (int)$member['id']; ?>#memberForm"
                                        class="btn btn-edit"
                                    >
                                        EDIT
                                    </a>


                                    <!-- HAPUS -->

                                    <form
                                        method="POST"
                                        action="Keanggotaan.php"
                                        onsubmit="return confirm('Yakin ingin menghapus anggota ini?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$member['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="hapus"
                                            class="btn-delete"
                                        >
                                            HAPUS
                                        </button>

                                    </form>


                                    <!-- CETAK KARTU -->

                                    <button
                                        type="button"
                                        class="btn-print"
                                        onclick="printMemberCard(
                                            this.getAttribute('data-nama'),
                                            this.getAttribute('data-id'),
                                            this.getAttribute('data-prodi'),
                                            this.getAttribute('data-telp'),
                                            this.getAttribute('data-email')
                                        )"

                                        data-nama="<?= e($member['nama']); ?>"
                                        data-id="<?= e($member['nim_nik']); ?>"
                                        data-prodi="<?= e($member['program_studi']); ?>"
                                        data-telp="<?= e($member['no_telp']); ?>"
                                        data-email="<?= e($member['email']); ?>"
                                    >
                                        PRINT KARTU
                                    </button>


                                </div>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td colspan="7" class="empty-data">

                            Belum ada anggota yang terdaftar.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </section>


</main>



<!-- ==========================================
     KARTU ANGGOTA
========================================== -->

<div id="cardArea">

    <div class="card-title">
        Libs
    </div>

    <div class="card-subtitle">
        LIBRARY MEMBERSHIP CARD
    </div>

    <div class="card-details">

        <p>
            <strong>Nama:</strong>
            <span id="cNama">-</span>
        </p>

        <p>
            <strong>ID Anggota:</strong>
            <span id="cID">-</span>
        </p>

        <p>
            <strong>Program Studi:</strong>
            <span id="cProdi">-</span>
        </p>

        <p>
            <strong>No. Telepon:</strong>
            <span id="cTelp">-</span>
        </p>

        <p>
            <strong>Email:</strong>
            <span id="cEmail">-</span>
        </p>

    </div>

    <div class="card-footer">
        Libs · BOOKS, PEOPLE, BRIGHTER DAYS
    </div>

</div>



<!-- ==========================================
     FOOTER
========================================== -->

<footer class="no-print">

    © <?= date('Y'); ?> Libs · BOOKS, PEOPLE, BRIGHTER DAYS

</footer>



<!-- ==========================================
     JAVASCRIPT
========================================== -->

<script>


// ==========================================
// PENCARIAN ANGGOTA
// ==========================================

function searchMembers() {

    var input = document.getElementById("searchMember");

    var filter = input.value.toLowerCase();

    var table = document.getElementById("memberTable");

    var rows = table.getElementsByTagName("tr");


    for (var i = 1; i < rows.length; i++) {

        var text = rows[i].innerText.toLowerCase();

        if (text.indexOf(filter) > -1) {

            rows[i].style.display = "";

        } else {

            rows[i].style.display = "none";

        }

    }

}



// ==========================================
// GENERATE DAN PRINT KARTU ANGGOTA
// ==========================================

function printMemberCard(nama, idAnggota, prodi, telp, email) {

    document.getElementById("cNama").innerText = nama;

    document.getElementById("cID").innerText = idAnggota;

    document.getElementById("cProdi").innerText = prodi;

    document.getElementById("cTelp").innerText = telp;

    document.getElementById("cEmail").innerText = email;


    document.getElementById("cardArea").style.display = "block";


    window.print();


    document.getElementById("cardArea").style.display = "none";

}

</script>


</body>
</html>