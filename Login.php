<?php
session_start();
// Sertakan file koneksi database
include 'db_config.php'; // Pastikan $conn didefinisikan di sini

// --- DATA LOGIN DEFAULT ADMIN (UNTUK LATIHAN) ---
$default_email = "admin@libs.com"; 
$default_password = "admin";

$error = "";

// Jika tombol login ditekan
if (isset($_POST['login'])) {
    // 1. Ambil input
   $email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($email === "" || $password === "") {
        $error = "Email dan password harus diisi.";
    } else {
        
        // --- CEK 1: LOGIN ADMIN DEFAULT (HARDCODED) ---
        if ($email === $default_email && $password === $default_password) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_email'] = $default_email;
            $_SESSION['user_nama'] = 'Administrator (Default)';
            header("Location: beranda.php");
            exit;
        }
        
        // --- CEK 2: LOGIN DARI DATABASE ---
        
        // Gunakan prepared statement untuk keamanan (mencegah SQL Injection)
        $stmt = $conn->prepare("SELECT password_hash, nama FROM tbl_anggota WHERE email = ?");
        
        if ($stmt === false) {
             $error = "Terjadi kesalahan server saat mempersiapkan query: " . $conn->error;
        } else {
            // Bind parameter dan eksekusi
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                // Ambil hash password dan nama
                $stmt->bind_result($password_hash, $nama);
                $stmt->fetch();
                
                // Verifikasi Password menggunakan hash
                if (password_verify($password, $password_hash)) {
                    // Login Berhasil dari Database!
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_nama'] = $nama;
                    
                    header("Location: beranda.php");
                    exit;
                } else {
                    $error = "Email atau password salah.";
                }
            } else {
                $error = "Email atau password salah.";
            }

            $stmt->close();
        }
    }
}

// Tutup koneksi setelah selesai memproses request POST
if (isset($conn)) {
    $conn->close();
}
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>libs — Login Akun</title>

  <style>
    body{ margin:0; font-family:Arial, sans-serif; height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(180deg,#FFE4B5,#FFA07A); }
    .card{ width:340px; background:white; padding:24px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.1); }
    h2{margin:0 0 10px;color:#333;font-size:20px;text-align:center}
    label{font-size:14px;color:#333;margin-top:12px;display:block}
    input{ width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; margin-top:5px; }
    button{ width:100%; padding:10px; border:0; border-radius:8px; background:#FFA07A; color:white; font-weight:bold; cursor:pointer; margin-top:16px; }
    .error{ background:#ffe5e5; color:#b30000; padding:8px; border-radius:6px; font-size:13px; margin-top:10px; }
    .footer{ font-size:12px; text-align:center; margin-top:10px; color:#666; }
    .register-link { display: block; margin-top: 8px; text-align: center; font-size: 13px; color: #FFA07A; text-decoration: none; }
    .admin-hint { font-size: 11px; text-align: center; color: #f08080; margin-top: 5px; }
  </style>
<link rel="stylesheet" href="assets/libs.css">
</head>

<body>
  <div class="card">
    <h2>libs — Login</h2>

    <?php if($error != "") : ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>Email</label>
      <input type="email" name="email" placeholder="masukkan email anda" required
           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

      <label>Password</label>
      <input type="password" name="password" placeholder="masukkan password" required>

      <button type="submit" name="login">Masuk</button>
    </form>

    <a href="register.php" class="register-link">
        Belum punya akun? Daftar di sini
    </a>

    <div style="margin-top:10px; text-align:center;">
      <a href="lupa_password.php" style="font-size:13px; color:#d35400; text-decoration:none;">
        Lupa Password?
      </a>
    </div>
    
    <div class="admin-hint">
        (Coba: email: **admin@libs.com** | password: **admin**)
    </div>

    <div class="footer">
      Sistem Informasi Perpustakaan libs
    </div>
  </div>
</body>
</html>