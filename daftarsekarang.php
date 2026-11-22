<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_config.php';

$error = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    email=isset(_POST['email']) ? strtolower(trim($_POST['email'])) : '';
    password=isset(_POST['password']) ? $_POST['password'] : '';
    $email_value = $email;

    if ($email === '' || $password === '') {
        $error = 'Email dan password harus diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Akun admin bawaan untuk latihan.
        $default_email = 'admin@libs.com';
        $default_password = 'admin';

        if ($email === $default_email && $password === $default_password) {
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['user_email'] = $default_email;
            $_SESSION['user_nama'] = 'Administrator (Default)';

            $conn->close();
            header('Location: beranda.php');
            exit;
        }

        // Email pribadi dapat digunakan jika sudah terdaftar di tbl_anggota.
        $stmt = $conn->prepare(
            'SELECT password_hash, nama FROM tbl_anggota WHERE LOWER(email) = LOWER(?) LIMIT 1'
        );

        if ($stmt === false) {
            $error = 'Terjadi kesalahan saat menyiapkan query login: ' . $conn->error;
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                stmt->bindresult(password_hash, $nama);
                $stmt->fetch();

                if (password_verify($password, $password_hash)) {
                    session_regenerate_id(true);
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_nama'] = $nama;

                    $stmt->close();
                    $conn->close();
                    header('Location: beranda.php');
                    exit;
                }
            }

            $error = 'Email atau password salah.';
            $stmt->close();
        }
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>libs — Login Akun</title>
    <link rel="stylesheet" href="assets/libs.css">
    <style>
        body.login-page {
            min-height: 100vh;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3eadf;
        }

        .login-card {
            box-sizing: border-box;
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            padding: 34px;
            background: #fffaf4;
            border: 1px solid #d9cbbd;
            box-shadow: 0 8px 24px rgba(50, 30, 25, .08);
        }

        .login-card h2 {
            margin: 0 0 8px;
            color: #401d28;
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 27px;
            font-weight: 500;
            text-align: center;
        }

        .login-subtitle {
            margin: 0 0 22px;
            color: #786b63;
            font-size: 13px;
            text-align: center;
        }

        .login-card label {
            display: block;
            margin: 14px 0 6px;
            color: #2c201f;
            font-size: 13px;
            font-weight: 600;
        }

        .login-card input {
            display: block;
            box-sizing: border-box;
            width: 100%;
            min-height: 44px;
            padding: 11px 12px;
            border: 1px solid #d9cbbd;
            border-radius: 0;
            background: #fff;
            color: #2c201f;
            font-size: 14px;
        }

        .login-card button {
            display: block;
            width: 100%;
            min-height: 45px;
            margin-top: 22px;
            padding: 12px;
            border: 0;
            border-radius: 0;
            background: #401d28;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            cursor: pointer;
        }

        .login-card button:hover {
            background: #68404a;
        }

        .login-error {
            margin: 0 0 14px;
            padding: 10px 12px;
            border: 1px solid #d8a5a5;
            background: #fff0ed;
            color: #9b2929;
            font-size: 13px;
        }

        .login-links {
            margin-top: 17px;
            text-align: center;
            font-size: 13px;
        }

        .login-links a {
            display: inline-block;
            margin: 5px 7px;
            color: #542a35;
            text-decoration: none;
        }

        .login-links a:hover {
            text-decoration: underline;
        }

        .login-admin-hint {
            margin-top: 18px;
            color: #786b63;
            text-align: center;
            font-size: 11px;
        }

        .login-footer {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px solid #d9cbbd;
            color: #786b63;
            text-align: center;
            font-size: 11px;
            letter-spacing: .5px;
        }

        @media (max-width: 480px) {
            body.login-page {
                padding: 16px;
            }
            .login-card {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <h2>libs — Login</h2>
        <p class="login-subtitle">Masuk ke Sistem Informasi Perpustakaan</p>

        <?php if ($error !== ''): ?>
            <div class="login-error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Masukkan email Anda"
                autocomplete="email"
                required
                value="<?php echo htmlspecialchars($email_value, ENT_QUOTES, 'UTF-8'); ?>"
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Masukkan password"
                autocomplete="current-password"
                required
            >

            <button type="submit" name="login">Masuk</button>
        </form>

        <div class="login-links">
            <a href="register.php">Belum punya akun? Daftar di sini</a>
            <br>
            <a href="lupa_password.php">Lupa Password?</a>
        </div>

        <div class="login-admin-hint">
            Akun latihan: admin@libs.com / admin
        </div>

        <div class="login-footer">
            Sistem Informasi Perpustakaan LIBS
        </div>
    </div>
</body>
</html>