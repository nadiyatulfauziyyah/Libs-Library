<?php
/**
 * header.php — Header navigasi bersama untuk semua halaman libs.
 *
 * Cara pakai (di dalam <body>, sebelum konten halaman):
 *     <?php $lb_active = 'opac'; include 'header.php'; ?>
 *
 * Nilai $lb_active: beranda | opac | koleksi | anggota | sirkulasi
 * Untuk mengubah nama/urutan menu, cukup edit array $lb_menu di bawah ini.
 */
$lb_active = isset($lb_active) ? $lb_active : '';
$lb_menu = [
    'beranda'   => ['Beranda.php',    'BERANDA'],
    'opac'      => ['OPAC.php',       'KATALOG OPAC'],
    'koleksi'   => ['Bibliografi.php','KOLEKSI'],
    'anggota'   => ['Keanggotaan.php','KEANGGOTAAN'],
    'sirkulasi' => ['Sirkulasi.php',  'SIRKULASI'],
    'keluar'    => ['Logout.php',     'KELUAR'],
];
?>
<header class="lb-header no-print">
  <a class="lb-brand" href="Beranda.php"><span class="lb-name">Libs</span><span class="lb-dot" aria-hidden="true">·</span><span class="lb-tag">BOOKS, PEOPLE, BRIGHTER DAYS</span></a>
  <nav class="lb-nav">
<?php foreach ($lb_menu as $key => $item): ?>
    <a href="<?= $item[0] ?>"<?= $key === $lb_active ? ' class="active" aria-current="page"' : '' ?>><?= $item[1] ?></a>
<?php endforeach; ?>
  </nav>
</header>
