<?php
session_start();
session_destroy();
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="refresh" content="1; url=login.php">
  <title>Logout...</title>

  <style>
    body{
      margin:0;
      font-family:Arial, sans-serif;
      display:flex;
      height:100vh;
      align-items:center;
      justify-content:center;
      background:linear-gradient(180deg,#FFE4B5,#FFA07A);
      color:#333;
    }
    .box{
      background:white;
      padding:20px 26px;
      border-radius:10px;
      box-shadow:0 8px 18px rgba(0,0,0,0.1);
      text-align:center;
    }
  </style>
<link rel="stylesheet" href="assets/libs.css">
</head>

<body>
  <div class="box">
    <h3>Berhasil Logout</h3>
    <p>Mengembalikan ke halaman login...</p>
  </div>
</body>
</html>
