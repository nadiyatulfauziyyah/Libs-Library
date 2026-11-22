
<?php
session_start();
include 'db_config.php';

$error = "";
$message = "";
$step = 1;

// Data email dari session
$verified_email = isset($_SESSION['reset_email'])
    ? $_SESSION['reset_email']
    : null;

// Jika session email sudah tersedia, langsung ke langkah 2
if ($verified_email !== null) {
    $step = 2;
}

// --- LOGIKA UTAMA ---

if (isset($_POST["cek_email"])) {

    // 1. Logika Cek Email
    $email = isset($_POST["email"])
        ? trim($_POST["email"])
        : '';

    if (empty($email)) {
        $error = "Email wajib diisi.";
        $step = 1;

    } else {

        $stmt = $conn->prepare(
            "SELECT email FROM tbl_anggota WHERE email = ?"
        );

        if ($stmt === false) {
            $error = "Kesalahan server saat mempersiapkan query.";
            $step = 1;

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {

                // Email ditemukan
                $_SESSION['reset_email'] = $email;
                $verified_email = $email;
                $step = 2;

            } else {

                $error = "Alamat email tidak terdaftar.";
                $step = 1;
            }

            $stmt->close();
        }
    }

} elseif (isset($_POST["reset_password"]) && $verified_email) {

    // 2. Logika Reset Password

    $password_baru = isset($_POST["password_baru"])
        ? $_POST["password_baru"]
        : '';

    $konfirmasi_password = isset($_POST["konfirmasi_password"])
        ? $_POST["konfirmasi_password"]
        : '';

    if (
        empty($password_baru) ||
        $password_baru !== $konfirmasi_password ||
        strlen($password_baru) < 6
    ) {

        $error = "Password baru tidak valid, tidak cocok, atau kurang dari 6 karakter.";
        $step = 2;

    } else {

        // Hash password baru
        $password_hash_baru = password_hash(
            $password_baru,
            PASSWORD_DEFAULT
        );

        // Update password di database
        $update_stmt = $conn->prepare(
            "UPDATE tbl_anggota SET password_hash = ? WHERE email = ?"
        );

        if ($update_stmt === false) {

            $error = "Kesalahan server saat mempersiapkan update password.";
            $step = 2;

        } else {

            $update_stmt->bind_param(
                "ss",
                $password_hash_baru,
                $verified_email
            );

            if ($update_stmt->execute()) {

                $message = "Password berhasil diperbarui! Silakan Login.";

                unset($_SESSION['reset_email']);

                $step = 3;

            } else {

                $error = "Gagal memperbarui password.";
                $step = 2;
            }

            $update_stmt->close();
        }
    }
}

// Tutup koneksi database
if (isset($conn)) {
    $conn->close();
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Reset Password — LIBS</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(180deg, #FFE4B5, #FFA07A);
        }

        .card {
            width: 340px;
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        label {
            margin-top: 10px;
            display: block;
            font-size: 14px;
            color: #333;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            background: #FFA07A;
            border: 0;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .success {
            background: #e8ffe8;
            padding: 10px;
            border-radius: 8px;
            color: #0a7a0a;
            margin-top: 10px;
        }

        .error {
            background: #ffe5e5;
            padding: 8px;
            border-radius: 6px;
            color: #b30000;
            font-size: 13px;
            margin-top: 10px;
        }

        .back {
            text-align: center;
            margin-top: 15px;
        }
    </style>

    <link rel="stylesheet" href="assets/libs.css">
</head>

<body>

<div class="card">

    <h2>Reset Password</h2>

    <?php if ($error != "") : ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($message != "") : ?>
        <div class="success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($step == 1) : ?>

        <p style="font-size:13px; text-align:center; color:#666; margin-bottom:15px;">
            Masukkan email terdaftar Anda.
        </p>

        <form method="POST">

            <label>Masukkan Email</label>

            <input
                type="email"
                name="email"
                placeholder="email kamu"
                required
            >

            <button type="submit" name="cek_email">
                Cek Email
            </button>

        </form>

    <?php elseif ($step == 2) : ?>

        <p style="font-size:13px; text-align:center; color:#666; margin-bottom:15px;">
            Email <b><?php echo htmlspecialchars($verified_email); ?></b>
            ditemukan. Masukkan password baru.
        </p>

        <form method="POST">

            <label>Password Baru (min 6 karakter)</label>

            <input
                type="password"
                name="password_baru"
                required
            >

            <label>Konfirmasi Password Baru</label>

            <input
                type="password"
                name="konfirmasi_password"
                required
            >

            <button type="submit" name="reset_password">
                Ganti Password
            </button>

        </form>

    <?php elseif ($step == 3) : ?>

        <div class="back">
            <a href="login.php"
               style="text-decoration:none; color:#FFA07A; font-weight:bold;">
                &rarr; Kembali ke Login
            </a>
        </div>

    <?php endif; ?>

    <?php if ($step != 3) : ?>

        <div class="back">
            <a href="login.php"
               style="text-decoration:none; color:#d35400;">
                &larr; Kembali ke Login
            </a>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
