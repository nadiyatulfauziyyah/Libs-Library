<?php
session_start();
if (!isset($_SESSION["user_nama"])) {
    header("Location: login.php");
    exit;
}
$display_name = $_SESSION["user_nama"];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Libs — Books, People, Brighter Days</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="assets/libs.css?v=<?= @filemtime(__DIR__ . '/assets/libs.css') ?>" rel="stylesheet">
<style>
.home-hero{min-height:590px;display:grid;grid-template-columns: .9fr 1.1fr;background:#351820;color:#f7eee5;overflow:hidden}
.hero-copy{padding:clamp(42px,7vw,92px);display:flex;flex-direction:column;justify-content:center;align-items:flex-start}
.hero-copy .eyebrow,.eyebrow{font-size:10px;letter-spacing:3px;text-transform:uppercase;color:#d4bda9}
.hero-copy h1{font-size:clamp(48px,6vw,78px)!important;line-height:.98!important;color:#f8eee4!important;margin:22px 0}
.hero-copy p{max-width:340px;color:#e1d2c7;font-family:'Playfair Display',Georgia,serif;font-size:19px;line-height:1.4}
.hero-cta{display:inline-block;margin-top:20px;background:#f3e9dd;color:#33201f;text-decoration:none;padding:14px 22px;font-size:10px;letter-spacing:1.5px;text-transform:uppercase}
.hero-photo{min-height:460px;background:linear-gradient(90deg,rgba(53,24,32,.12),transparent),url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1500&q=85') center/cover}
.welcome-strip{padding:18px clamp(22px,6vw,72px);background:#e8dbcd;color:#5b4b43;font-size:12px;letter-spacing:.5px}
.section{padding:54px clamp(22px,6vw,72px) 62px}
.section-heading{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:28px}
.section-heading h2{font-size:36px!important;margin:0}
.section-heading a{color:var(--wine);font-size:10px;letter-spacing:1.5px;text-decoration:none;text-transform:uppercase}
.book-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:26px}
.book-card{min-width:0}
.book-cover{height:235px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#d6c2aa;box-shadow:0 7px 16px rgba(40,24,20,.12)}
.book-cover img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.book-card:hover img{transform:scale(1.04)}
.book-card h3{font-size:18px!important;margin:14px 0 2px}
.book-card p{font-family:'Playfair Display',Georgia,serif;color:#6e5d53;margin:0;font-size:14px}
.book-card small{display:block;margin-top:8px;color:#8b7769;font-size:9px;letter-spacing:1.6px;text-transform:uppercase}
.feature-row{display:grid;grid-template-columns:1.2fr .8fr;min-height:340px}
.feature-image{background:url('https://images.unsplash.com/photo-150 кітап?auto=format&fit=crop&w=1400&q=85') center/cover}
.feature-image{background-image:url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1400&q=85')}
.event-panel{background:#3b1c27;color:#f6eee6;padding:clamp(30px,5vw,62px);display:flex;flex-direction:column;justify-content:center}
.event-panel .eyebrow{color:#cdb7a7}
.event-panel h2{color:#f6eee6!important;font-size:38px!important;margin:14px 0}
.event-panel p{color:#e0d0c5;font-family:'Playfair Display',Georgia,serif;font-size:17px}
.event-panel a{align-self:flex-start;color:#2e1d1c;background:#f3e8dc;text-decoration:none;padding:12px 18px;font-size:10px;letter-spacing:1px;margin-top:10px}
.quick-links{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:32px}
.quick-link{padding:22px;background:#e8dbcd;color:var(--wine);text-decoration:none;border:1px solid #d9cabb}
.quick-link span{display:block;font-family:'Playfair Display',Georgia,serif;font-size:23px}
.quick-link small{font-size:10px;letter-spacing:1px;color:#77675e}
@media(max-width:800px){.home-hero{grid-template-columns:1fr}.hero-photo{min-height:330px;grid-row:1}.hero-copy{padding:38px 26px}.book-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}.book-cover{height:210px}.feature-row{grid-template-columns:1fr}.feature-image{min-height:260px}.quick-links{grid-template-columns:repeat(2,1fr)}}
@media(max-width:420px){.book-cover{height:170px}.section-heading h2{font-size:29px!important}.quick-links{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<?php $lb_active = 'beranda'; include 'header.php'; ?>
<section class="home-hero">
  <div class="hero-copy">
    <div class="eyebrow">A little space for big ideas</div>
    <h1>Read<br>something<br>unexpected.</h1>
    <p>Temukan cerita, gagasan, dan pengetahuan baru. Selamat datang di ruang baca libs.</p>
    <a class="hero-cta" href="opac.php">Jelajahi koleksi buku&nbsp; →</a>
  </div>
  <div class="hero-photo" role="img" aria-label="Rak buku di ruang baca yang hangat"></div>
</section>
<div class="welcome-strip">Selamat datang kembali, <?= htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8'); ?>. Temukan bacaan berikutnya di libs.</div>
<section class="section">
  <div class="section-heading"><h2>Curated for curious readers</h2><a href="opac.php">Lihat semua koleksi →</a></div>
  <div class="book-grid">
    <article class="book-card"><div class="book-cover"><img src="https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=600&q=85" alt="Tumpukan buku"></div><h3>Stories to Remember</h3><p>Literature & Fiction</p><small>Fiction</small></article>
    <article class="book-card"><div class="book-cover"><img src="https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=600&q=85" alt="Buku terbuka"></div><h3>The Art of Reading</h3><p>Ideas for everyday life</p><small>Non-fiction</small></article>
    <article class="book-card"><div class="book-cover"><img src="https://images.unsplash.com/photo-1519682337058-a94d519337bc?auto=format&fit=crop&w=600&q=85" alt="Buku dan suasana hangat"></div><h3>Quiet Discoveries</h3><p>Find a new perspective</p><small>Inspiration</small></article>
    <article class="book-card"><div class="book-cover"><img src="https://images.unsplash.com/photo-1516979187457-637abb4f9353?auto=format&fit=crop&w=600&q=85" alt="Rak buku"></div><h3>Between the Pages</h3><p>A world of possibilities</p><small>Collection</small></article>
  </div>
  <div class="quick-links">
    <a class="quick-link" href="opac.php"><span>OPAC ↗</span><small>Telusuri katalog buku</small></a>
    <a class="quick-link" href="bibliografi.php"><span>Bibliografi ↗</span><small>Kelola koleksi</small></a>
    <a class="quick-link" href="keanggotaan.php"><span>Keanggotaan ↗</span><small>Data anggota</small></a>
    <a class="quick-link" href="sirkulasi.php"><span>Sirkulasi ↗</span><small>Peminjaman & pengembalian</small></a>
  </div>
</section>
<section class="feature-row">
  <div class="feature-image" role="img" aria-label="Interior perpustakaan"></div>
  <div class="event-panel"><div class="eyebrow">Your library, your next chapter</div><h2>A place to pause, read & grow.</h2><p>Ruang untuk menemukan bacaan, merawat rasa ingin tahu, dan membawa pulang ide-ide baru.</p><a href="opac.php">MULAI MENJELAJAH →</a></div>
</section>
<footer>© <?= date('Y') ?> Libs · BOOKS, PEOPLE, BRIGHTER DAYS</footer>
</body>
</html>
