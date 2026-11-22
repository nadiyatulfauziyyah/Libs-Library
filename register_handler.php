<?php
// File: register_handler.php
// Menangani proses pendaftaran akun baru (dipanggil dari register.php lewat fetch JSON)

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

function kirim_respons($data) {
    echo json_encode($data);
    exit;
}

include 'db_config.php'; // mendefinisikan $conn

try {

    // Ambil data JSON dari register.php
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!is_array($data)) {
        kirim_respons(array(
            'success' => false,
            'message' => 'Data registrasi tidak valid.'
        ));
    }

    // Ambil nilai formulir (nama key sesuai yang dikirim register.php)
    $nama     = isset($data['nama'])     ? trim($data['nama'])     : '';
    $nim_nik  = isset($data['idnum'])    ? trim($data['idnum'])    : '';
    $prodi    = isset($data['prodi'])    ? trim($data['prodi'])    : '';
    $email    = isset($data['email'])    ? trim($data['email'])    : '';
    $password = isset($data['password']) ? $data['password']       : '';
    $telp     = isset($data['telp'])     ? trim($data['telp'])     : '';

    // Validasi
    if ($nama == '' || $nim_nik == '' || $prodi == '' || $email == '' || $password == '') {
        kirim_respons(array(
            'success' => false,
            'message' => 'Data wajib (Nama, NIM/NIK, Prodi, Email, Password) harus diisi lengkap.'
        ));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        kirim_respons(array(
            'success' => false,
            'message' => 'Format email tidak valid.'
        ));
    }

    if (strlen($password) < 6) {
        kirim_respons(array(
            'success' => false,
            'message' => 'Password minimal 6 karakter.'
        ));
    }

    // Cek duplikasi Email atau NIM/NIK (kolom primary key tbl_anggota adalah "id")
    $check = $conn->prepare("SELECT id FROM tbl_anggota WHERE email = ? OR nim_nik = ?");

    if (!$check) {
        kirim_respons(array(
            'success' => false,
            'message' => 'Gagal memeriksa data: ' . $conn->error
        ));
    }

    $check->bind_param("ss", $email, $nim_nik);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        $conn->close();

        kirim_respons(array(
            'success' => false,
            'message' => 'Gagal: Email atau NIM/NIK ini sudah terdaftar.'
        ));
    }

    $check->close();

    // Enkripsi (hash) password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // No. telepon boleh kosong (disimpan sebagai NULL)
    $telp_value = ($telp == '') ? null : $telp;

    // INSERT sesuai struktur tabel tbl_anggota:
    // nama, nim_nik, program_studi, no_telp, email, password_hash
    $sql = "INSERT INTO tbl_anggota
            (nama, nim_nik, program_studi, no_telp, email, password_hash)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        kirim_respons(array(
            'success' => false,
            'message' => 'Gagal menyiapkan penyimpanan: ' . $conn->error
        ));
    }

    $stmt->bind_param(
        "ssssss",
        $nama,
        $nim_nik,
        $prodi,
        $telp_value,
        $email,
        $password_hash
    );

    if ($stmt->execute()) {

        $id_baru = $conn->insert_id;

        $stmt->close();
        $conn->close();

        kirim_respons(array(
            'success' => true,
            'message' => 'Registrasi berhasil! Akun Anda telah dibuat. Silakan login.',
            'new_id'  => $id_baru
        ));

    } else {

        $error = $stmt->error;

        $stmt->close();
        $conn->close();

        kirim_respons(array(
            'success' => false,
            'message' => 'Gagal menyimpan data: ' . $error
        ));
    }

} catch (Throwable $e) {
    // Pastikan respons tetap JSON jika terjadi error database
    kirim_respons(array(
        'success' => false,
        'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
    ));
}
?>
