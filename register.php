<?php
// Backend kosong, bisa ditambahkan nanti
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>libs — Registrasi Akun Baru</title>
<style>
/* --- (CSS ASLI ANDA) --- */
body{ margin:0; font-family:Arial, sans-serif; background:#f5f7fb; color:#333; }
header{ background:#ff9966; color:white; padding:14px 28px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 3px 10px rgba(0,0,0,0.15); }
header nav a{ color:white; text-decoration:none; margin-left:12px; font-weight:bold; }
.container{ max-width:400px; margin:40px auto; font-family:Arial, sans-serif; }
form{ background:white; padding:20px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.1); }
form h2{ text-align:center; color:#FF9966; margin-bottom:15px; }
form label{ display:block; margin-top:10px; margin-bottom:5px; font-size:14px; }
form input{ width:100%; padding:8px; border-radius:6px; border:1px solid #ddd; }
button{ width:100%; padding:10px; border:none; border-radius:8px; font-weight:bold; cursor:pointer; margin-top:10px; color:white; }
.btn-register{ background:#34d399; }
</style>
<link rel="stylesheet" href="assets/libs.css">
</head>
<body>

<header>
  <div style="font-size:22px;font-weight:bold">📚 libs Library</div>
  <nav>
    <a href="beranda.php">Beranda</a>
    <a href="login.php">Login</a>
  </nav>
</header>

<div class="container">
  <h1 style="text-align:center; color:#FA8072;">🔑 Buat Akun libs</h1>
  <p style="text-align:center; color:#475569; font-size:14px;">Daftar untuk mendapatkan akses ke layanan perpustakaan.</p>

    <form id="registerForm">
      <h2>Form Registrasi</h2>
      
            <label>Nama Lengkap</label>
      <input type="text" id="nama" placeholder="Nama Lengkap Anda" required>

      <label>NIM / NIK</label>
      <input type="text" id="idnum" placeholder="BK-001 / NIM/NIK" required>       <label>Program Studi / Departemen</label>
      <input type="text" id="prodi" placeholder="Contoh: Ilmu Perpustakaan" required>       <label>No. Telepon (Opsional)</label>
      <input type="text" id="telp" placeholder="0812xxxxxxx">

      <h3 style="font-size: 16px; color: #ff9966; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">Kredensial Akun</h3>
      
      <label>Email (Untuk Login)</label>
      <input type="email" id="email" placeholder="email@example.com" required>

      <label>Password</label>
      <input type="password" id="password" placeholder="Minimal 6 karakter" required>

      <label>Konfirmasi Password</label>
      <input type="password" id="confirm_password" placeholder="Ketik ulang password" required>

      <button type="button" class="btn-register" onclick="handleRegistration()">Daftar Akun Baru</button>
      
      <p style="text-align:center; margin-top:15px; font-size:13px;">
          Sudah punya akun? <a href="login.php" style="color:#60a5fa; text-decoration:none; font-weight:bold;">Login di sini</a>
      </p>
  </form>
</div>

<script> 
function handleRegistration() {
    // Ambil semua data, termasuk yang baru
    const nama = document.getElementById('nama').value.trim();
    const idnum = document.getElementById('idnum').value.trim(); // Baru
    const prodi = document.getElementById('prodi').value.trim(); // Baru
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const telp = document.getElementById('telp').value.trim();

    // 1. Validasi Sederhana (Field baru ditambahkan ke validasi wajib)
    if (!nama || !idnum || !prodi || !email || !password || !confirmPassword) {
        alert("Semua field (kecuali No. Telepon) wajib diisi!");
        return;
    }

    if (password !== confirmPassword) {
        alert("Konfirmasi Password tidak cocok!");
        return;
    }
    
    if (password.length < 6) {
        alert("Password minimal harus 6 karakter!");
        return;
    }

    // 2. Kumpulkan Data
    const dataRegister = {
        nama: nama,
        idnum: idnum, // Kirim ke server
        prodi: prodi, // Kirim ke server
        email: email,
        password: password,
        telp: telp,
    };

    // 3. Kirim ke Backend menggunakan Fetch API
    fetch('register_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dataRegister)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Jaringan bermasalah atau server error');
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            alert("✅ Registrasi berhasil! Silakan Login.");
            window.location.href = 'login.php'; 
        } else {
            alert("❌ Gagal Registrasi: " + result.message);
        }
    })
    .catch(error => {
        console.error('Error saat registrasi:', error);
        alert("Terjadi kesalahan teknis saat mendaftar. Cek konsol.");
    });
}
</script>

</body>
</html>