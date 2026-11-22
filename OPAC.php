<?php
session_start();
if (!isset($_SESSION["user_nama"])) {
    header("Location: login.php");
    exit;
}
$display_name = $_SESSION["user_nama"];

include 'db_config.php';

// Ambil data buku dari bibliografi/koleksi
$books = [];
$sql = "
    SELECT
        bg.ID        AS id,
        bg.Judul     AS judul,
        bg.Pengarang AS pengarang,
        bg.Kategori  AS kategori,
        bg.Tahun     AS tahun,
        bg.Cover     AS cover,
        b.stok_tersedia AS stok
    FROM tbl_blibliografi bg
    LEFT JOIN (
        SELECT id_bibliografi, SUM(stok_tersedia) AS stok_tersedia
        FROM tbl_buku
        GROUP BY id_bibliografi
    ) b ON b.id_bibliografi = bg.ID
    ORDER BY bg.Judul ASC
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $books[] = [
            "id"            => (int)$row["id"],
            "judul"         => $row["judul"],
            "pengarang"     => $row["pengarang"],
            "kategori"      => $row["kategori"] !== '' ? $row["kategori"] : 'Lainnya',
            "tahun"         => (string)$row["tahun"],
            "cover"         => !empty($row["cover"]) ? basename($row["cover"]) : '',
            "stok"          => $row["stok"] !== null ? (int)$row["stok"] : null,
        ];
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Katalog Buku — Libs OPAC</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/libs.css?v=<?= @filemtime(__DIR__ . '/assets/libs.css') ?>">
<style>
:root{--paper:#f4eadf;--paper-light:#fffaf3;--ink:#302321;--wine:#401d28;--wine2:#592d39;--muted:#82736a;--line:#dfd0c1;--gold:#b58b60}
*{box-sizing:border-box}
body{margin:0;background:var(--paper);color:var(--ink);font-family:'DM Sans',sans-serif;line-height:1.55}
.page-shell{max-width:1440px;margin:auto}
.opac-hero{min-height:450px;padding:64px clamp(24px,7vw,92px);display:flex;align-items:center;position:relative;overflow:hidden;background:linear-gradient(90deg,rgba(48,20,29,.96) 0%,rgba(48,20,29,.88) 43%,rgba(48,20,29,.08) 100%),url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1800&q=88') center 48%/cover;color:#f8eee4}
.hero-copy{max-width:880px;width:100%;position:relative;z-index:1}
.eyebrow{font-size:10px;letter-spacing:3px;text-transform:uppercase;color:#d7bfae}
.opac-hero h1,.opac-hero h1 *{font:400 clamp(48px,6vw,76px)/.99 'Playfair Display',serif;color:#fff!important}
.opac-hero h1{letter-spacing:-1.8px;margin:22px 0 16px}
.opac-hero p{max-width:440px;font:400 18px/1.5 'Playfair Display',serif;color:#f3e6da!important;margin:0 0 28px}

/* Pencarian bergaya Scopus */
.sx{max-width:860px;background:var(--paper-light);color:var(--ink);padding:26px 28px 20px;box-shadow:0 12px 35px #1d0e1460;border-top:3px solid var(--gold)}
.sx-row{display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.6fr);gap:12px;margin-bottom:12px}
.sx-row.has-op{grid-template-columns:104px minmax(0,.9fr) minmax(0,1.6fr) 38px;align-items:center}
.sx-box{border:1px solid #bfae9f;background:#fff;padding:8px 14px 6px;border-radius:4px;display:flex;flex-direction:column;justify-content:center;min-height:62px;min-width:0}
.sx-box:focus-within{border-color:var(--wine);box-shadow:0 0 0 1px var(--wine)}
.sx-box label{font:500 11px 'DM Sans',sans-serif;color:var(--wine2)}
.sx-box select,.sx-box input{width:100%;border:0!important;outline:0;background:transparent!important;padding:3px 0!important;font:500 14px 'DM Sans',sans-serif;color:var(--ink)!important}
.sx-rm{width:34px;height:34px;border:0!important;border-radius:50%!important;background:transparent!important;color:var(--wine)!important;font-size:22px!important;line-height:1;padding:0!important;cursor:pointer}
.sx-rm:hover{background:#eee0d1!important}
.sx-dates{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.sx-dates[hidden]{display:none}
.sx-dates .sx-box{min-width:130px}
.sx-actions{display:flex;flex-wrap:wrap;align-items:center;gap:6px 22px;margin-top:14px}
.sx-link{background:none!important;border:0!important;color:var(--wine)!important;font:500 13px 'DM Sans',sans-serif!important;cursor:pointer;padding:8px 0!important;display:inline-flex;align-items:center;gap:7px}
.sx-link:hover{color:var(--gold)!important}
.sx-link svg{width:18px;height:18px}
.sx-submit{margin-left:auto;background:var(--wine)!important;color:#fff!important;border:0!important;border-radius:4px;padding:13px 28px!important;font:600 13px 'DM Sans',sans-serif!important;cursor:pointer}
.sx-submit:hover{background:var(--wine2)!important}
@media(max-width:620px){.sx{padding:18px}.sx-row{grid-template-columns:1fr}.sx-row.has-op{grid-template-columns:1fr 38px}.sx-row.has-op .sx-box:nth-child(n+1){grid-column:1}.sx-row.has-op .sx-rm{grid-column:2;grid-row:1}.sx-submit{width:100%;margin-left:0}}

.content{padding:52px clamp(22px,5.4vw,76px) 70px}
.section-heading{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:24px}
.section-heading h2{font:400 clamp(28px,3vw,38px)/1.1 'Playfair Display',serif;color:var(--wine);margin:0}
.section-heading p{margin:7px 0 0;color:var(--muted);font-size:12px}
.result-count{font-size:11px;letter-spacing:.8px;color:var(--muted);white-space:nowrap}
.lens-banner{display:none;align-items:center;justify-content:space-between;gap:12px;background:#eee0d1;border-left:3px solid var(--wine);padding:12px 16px;margin:0 0 22px;font-size:13px;color:var(--wine)}
.lens-banner button{background:transparent!important;border:0!important;color:var(--wine)!important;font:600 10px 'DM Sans',sans-serif!important;letter-spacing:.8px;text-transform:uppercase;cursor:pointer;white-space:nowrap}
.category-row{display:flex;gap:9px;flex-wrap:wrap;margin:0 0 26px}
.category-chip{border:1px solid #d9c8b8!important;background:transparent!important;color:#67554d!important;padding:9px 15px!important;border-radius:30px!important;font:500 10px 'DM Sans',sans-serif!important;letter-spacing:.5px;cursor:pointer}
.category-chip.active,.category-chip:hover{background:var(--wine)!important;color:#fff!important;border-color:var(--wine)!important}
.results{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:28px 22px}
.book-card{min-width:0}
.book-card{background:var(--paper-light);border:1px solid var(--line);border-top:3px solid var(--wine);padding:20px 18px 16px;display:flex;flex-direction:column;transition:box-shadow .25s,transform .25s}
.book-card:hover{box-shadow:0 10px 24px #2b1a1822;transform:translateY(-2px)}
.book-category{align-self:flex-start;background:#eee0d1;color:var(--wine);padding:6px 9px;font-size:8px;letter-spacing:1.3px;text-transform:uppercase;margin-bottom:14px}
.book-info{padding:0;flex:1;display:flex;flex-direction:column}
.book-info h3{font:500 19px/1.2 'Playfair Display',serif;color:var(--wine);margin:0 0 5px}
.book-info .author{font:400 13px 'Playfair Display',serif;color:#716057;margin:0}
.book-meta{display:flex;justify-content:space-between;align-items:center;margin-top:12px;color:var(--muted);font-size:10px;letter-spacing:.4px}
.detail-link{background:none!important;border:0!important;color:var(--wine)!important;padding:4px 0!important;font:600 10px 'DM Sans',sans-serif!important;letter-spacing:.8px;text-transform:uppercase;cursor:pointer}
.detail-link:hover{color:var(--gold)!important}
.empty-state{display:none;padding:56px 20px;text-align:center;background:#fff8ef;border:1px solid var(--line);color:var(--muted)}
.empty-state h3{font:400 25px 'Playfair Display',serif;color:var(--wine);margin:0 0 8px}
.bottom-feature{display:grid;grid-template-columns:.85fr 1.15fr;background:var(--wine);color:#f7eee5;margin-top:66px;min-height:230px}
.feature-mark{display:flex;align-items:center;justify-content:center;border-right:1px solid #ffffff35;font:400 78px 'Playfair Display',serif;color:#e7d5c3}
.feature-copy{padding:35px clamp(24px,4vw,55px);align-self:center}
.feature-copy .eyebrow{color:#d3bcae}
.feature-copy h3{font:400 32px/1.12 'Playfair Display',serif;color:#fff5eb;margin:10px 0}
.feature-copy p{font:14px/1.6 'Playfair Display',serif;color:#e1d0c5;margin:0;max-width:480px}
.site-footer{border-top:1px solid var(--line);padding:25px clamp(22px,5.4vw,76px);display:flex;justify-content:space-between;gap:15px;color:var(--muted);font-size:9px;letter-spacing:1.2px}
.modal-backdrop{position:fixed;inset:0;background:#1b1010b8;backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:20px;z-index:50}
.modal-backdrop.open{animation:fadeIn .2s ease}
.modal{width:min(780px,100%);max-height:calc(100vh - 40px);overflow:auto;background:var(--paper-light);box-shadow:0 25px 70px #10080a70;position:relative;display:grid;grid-template-columns:250px 1fr;animation:rise .28s ease}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes rise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
.modal-close{position:absolute;right:14px;top:12px;z-index:2;width:34px;height:34px;border:0!important;border-radius:50%!important;background:transparent!important;color:var(--wine)!important;font-size:24px!important;line-height:1;padding:0!important;cursor:pointer}
.modal-close:hover{background:#eee0d1!important}
.modal-side{background:linear-gradient(160deg,#4a2230 0%,var(--wine) 60%,#2e1219 100%);color:#f7eee5;padding:34px 24px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;position:relative;overflow:hidden}
.modal-side::before{content:"";position:absolute;inset:14px;border:1px solid #ffffff2e;pointer-events:none}
.modal-cover{width:100%;max-width:170px;aspect-ratio:2/3;object-fit:cover;border:1px solid #ffffff40;box-shadow:0 10px 24px #00000060;margin-bottom:6px;display:block}
.modal-initial{font:400 112px/1 'Playfair Display',serif;color:#e7d5c3;margin-bottom:6px}
.modal-rule{width:40px;height:1px;background:var(--gold);margin:12px auto 16px}
.modal-side-cat{font-size:10px;letter-spacing:2.5px;text-transform:uppercase;color:#d7bfae}
.modal-side-year{font:italic 400 22px 'Playfair Display',serif;color:#f1e3d6;margin-top:8px}
.modal-body{padding:38px 36px 32px}
.modal-body h2{font:400 clamp(26px,3.4vw,34px)/1.12 'Playfair Display',serif;color:var(--wine);margin:10px 0 8px;padding-right:26px}
.modal-author{font:italic 400 17px 'Playfair Display',serif;color:var(--muted)}
.modal-info{margin:24px 0 0;border-top:1px solid var(--line)}
.modal-info div{display:flex;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid var(--line);font-size:13px}
.modal-info dt{color:var(--muted);font-size:10px;letter-spacing:1.4px;text-transform:uppercase;align-self:center}
.modal-info dd{margin:0;color:var(--ink);font-weight:500;text-align:right}
.modal-related{margin-top:24px}
.modal-related h4{margin:0 0 10px;font:600 10px 'DM Sans',sans-serif;letter-spacing:1.4px;text-transform:uppercase;color:var(--gold)}
.related-list{display:flex;flex-wrap:wrap;gap:8px}
.related-btn{border:1px solid #d9c8b8!important;background:transparent!important;color:var(--wine)!important;padding:8px 13px!important;font:500 12px 'Playfair Display',serif!important;cursor:pointer;text-align:left}
.other-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.other-item{border:0!important;background:transparent!important;padding:0!important;text-align:left;cursor:pointer;color:var(--ink)!important;min-width:0}
.other-thumb{width:100%;aspect-ratio:2/3;object-fit:cover;border:1px solid var(--line);display:block;background:#efe3d6}
.other-thumb.empty{display:flex;align-items:center;justify-content:center;font:400 34px 'Playfair Display',serif;color:var(--wine)}
.other-title{font:500 12px/1.25 'Playfair Display',serif;color:var(--wine);margin-top:6px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.other-author{font-size:10px;color:var(--muted)}
.other-item:hover .other-title{color:var(--gold)}
.related-btn:hover{background:var(--wine)!important;color:#fff!important;border-color:var(--wine)!important}

/* Pencarian kamera */
.lens-backdrop{position:fixed;inset:0;background:#1b1010d0;backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:16px;z-index:60}
.lens-modal{width:min(520px,100%);max-height:calc(100vh - 32px);overflow:auto;background:var(--paper-light);padding:28px 24px 24px;position:relative;box-shadow:0 25px 70px #10080a70;animation:rise .28s ease}
.lens-modal h3{font:400 28px 'Playfair Display',serif;color:var(--wine);margin:0 0 4px;padding-right:30px}
.lens-modal p{margin:0;color:var(--muted);font-size:12px}
.lens-stage{position:relative;background:#1b1010;aspect-ratio:4/3;overflow:hidden;margin:16px 0 12px}
.lens-stage video{width:100%;height:100%;object-fit:cover;display:block}
.lens-frame{position:absolute;inset:10% 14%;border:2px solid #ffffffd0;border-radius:10px;pointer-events:none;box-shadow:0 0 0 999px #00000045}
.lens-status{min-height:20px;font-size:12px;color:var(--wine);margin-bottom:12px}
.lens-actions{display:flex;gap:10px;flex-wrap:wrap}
.lens-actions .lens-btn{flex:1;min-width:140px;text-align:center;padding:13px 16px!important;font:600 10px 'DM Sans',sans-serif!important;letter-spacing:1.2px;text-transform:uppercase;cursor:pointer;border:1px solid var(--wine)!important}
.lens-btn.primary{background:var(--wine)!important;color:#fff!important}
.lens-btn.primary:hover{background:var(--wine2)!important}
.lens-btn.ghost{background:transparent!important;color:var(--wine)!important;display:inline-block}
.lens-btn.ghost:hover{background:#eee0d1!important}
.lens-btn[disabled]{opacity:.5;cursor:wait}

@media(max-width:900px){.results{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:620px){.opac-hero{min-height:420px;padding:50px 24px;background-position:58% center}.opac-hero h1{font-size:48px}.content{padding:38px 18px 48px}.results{grid-template-columns:repeat(2,minmax(0,1fr));gap:25px 14px}.book-info h3{font-size:17px}.bottom-feature{grid-template-columns:1fr}.feature-mark{display:none}.feature-copy{padding:30px 24px}.site-footer{flex-direction:column}.modal{grid-template-columns:1fr}.modal-side{flex-direction:row;gap:18px;padding:22px 24px;justify-content:flex-start;text-align:left}.modal-side::before{display:none}.modal-initial{font-size:60px;margin:0}.modal-rule{display:none}.modal-body{padding:24px 22px 26px}}
@media(max-width:620px){.modal-cover{max-width:90px;margin:0}}
@media(max-width:380px){.results{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php $lb_active = 'opac'; include 'header.php'; ?>

<div class="page-shell">
  <section class="opac-hero">
    <div class="hero-copy">
      <div class="eyebrow">libs · ONLINE PUBLIC ACCESS CATALOG</div>
      <h1>Find your<br>next read.</h1>
      <p>Jelajahi koleksi, temukan penulis favorit, dan buka pintu menuju cerita berikutnya.</p>

      <form id="searchForm" class="sx" autocomplete="off">
        <div id="sxRows"></div>
        <div class="sx-dates" id="sxDates" hidden>
          <div class="sx-box"><label for="yearFrom">Tahun terbit dari</label><select id="yearFrom"><option value="">Semua</option></select></div>
          <span>–</span>
          <div class="sx-box"><label for="yearTo">Sampai</label><select id="yearTo"><option value="">Semua</option></select></div>
          <button type="button" class="sx-link" id="removeDate">Hapus rentang ×</button>
        </div>
        <div class="sx-actions">
          <button type="button" class="sx-link" id="addField">＋ Tambah kolom pencarian</button>
          <button type="button" class="sx-link" id="addDate">▦ Tambah rentang tahun</button>
          <button type="button" class="sx-link" id="lensOpen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8a2 2 0 0 1 2-2h1.5l1.2-1.8A1 1 0 0 1 9.5 4h5a1 1 0 0 1 .8.2L16.5 6H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8z"/><circle cx="12" cy="12.5" r="3.5"/></svg> Cari dengan kamera</button>
          <button type="button" class="sx-link" id="resetBtn">Atur ulang</button>
          <button type="submit" class="sx-submit">Cari ⌕</button>
        </div>
      </form>
    </div>
  </section>

  <main class="content">
    <div class="section-heading">
      <div><div class="eyebrow" style="color:var(--gold)">DISCOVER SOMETHING NEW</div><h2>Jelajahi koleksi buku</h2><p>Pilih bacaan yang sesuai dengan rasa ingin tahumu.</p></div>
      <div class="result-count" id="resultCount" aria-live="polite"></div>
    </div>
    <div class="lens-banner" id="lensBanner"><span id="lensBannerText"></span><button type="button" id="lensClear">Hapus ×</button></div>
    <div class="category-row" id="categoryChips"></div>
    <section id="results" class="results" aria-live="polite"></section>
    <div class="empty-state" id="empty"><h3>Buku belum ditemukan</h3><p>Coba kata kunci atau filter yang berbeda.</p><button type="button" class="detail-link" id="emptyReset">Hapus semua filter →</button></div>

    <section class="bottom-feature">
      <div class="feature-mark">B.</div>
      <div class="feature-copy"><div class="eyebrow">YOUR NEXT CHAPTER STARTS HERE</div><h3>One search away<br>from a new world.</h3><p>Gunakan katalog libs untuk menemukan bacaan, menjelajahi kategori, dan mengenal koleksi perpustakaan.</p></div>
    </section>
  </main>
  <footer class="site-footer"><span>© <?= date('Y') ?> Libs</span><span>BOOKS, PEOPLE, BRIGHTER DAYS</span></footer>
</div>

<!-- Modal detail buku -->
<div class="modal-backdrop" id="modalBackdrop" role="dialog" aria-modal="true" aria-labelledby="mdTitle">
  <div class="modal">
    <button class="modal-close" id="closeModal" aria-label="Tutup detail">×</button>
    <aside class="modal-side">
      <img class="modal-cover" id="mdCover" alt="Sampul buku" style="display:none">
      <div class="modal-initial" id="mdInitial"></div>
      <div>
        <div class="modal-rule"></div>
        <div class="modal-side-cat" id="mdSideCat"></div>
        <div class="modal-side-year" id="mdSideYear"></div>
      </div>
    </aside>
    <div class="modal-body">
      <div class="eyebrow" style="color:var(--gold)">BOOK DETAILS</div>
      <h2 id="mdTitle"></h2>
      <div class="modal-author" id="mdAuthor"></div>
      <dl class="modal-info">
        <div><dt>Kategori</dt><dd id="mdKategori"></dd></div>
        <div><dt>Tahun terbit</dt><dd id="mdTahun"></dd></div>
        <div><dt>Stok tersedia</dt><dd id="mdStok"></dd></div>
      </dl>
      <div class="modal-related" id="mdRelatedWrap"><h4>Buku lain dari pengarang ini</h4><div class="related-list" id="mdRelated"></div></div>
      <div class="modal-related" id="mdOtherWrap"><h4>Buku lain di perpustakaan</h4><div class="other-grid" id="mdOther"></div></div>
    </div>
  </div>
</div>

<!-- Modal pencarian kamera -->
<div class="lens-backdrop" id="lensBackdrop" role="dialog" aria-modal="true" aria-labelledby="lensTitle">
  <div class="lens-modal">
    <button class="modal-close" id="lensClose" aria-label="Tutup kamera">×</button>
    <h3 id="lensTitle">Cari dengan kamera</h3>
    <p>Arahkan kamera ke sampul buku sampai judul/pengarang terlihat jelas, lalu ambil foto.</p>
    <div class="lens-stage">
      <video id="lensVideo" playsinline muted autoplay></video>
      <div class="lens-frame"></div>
    </div>
    <canvas id="lensCanvas" hidden></canvas>
    <div class="lens-status" id="lensStatus" aria-live="polite"></div>
    <div class="lens-actions">
      <button type="button" class="lens-btn primary" id="lensShot">Ambil foto &amp; cari</button>
      <label class="lens-btn ghost">Pilih dari galeri
        <input type="file" accept="image/*" id="lensFile" hidden>
      </label>
    </div>
  </div>
</div>

<script>
const books = <?php echo json_encode($books, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
const $ = id => document.getElementById(id);
const resultsEl = $('results'), emptyEl = $('empty');
let activeChip = '';
let lensMatches = null; // Map id -> skor (hasil pencarian kamera)

/* ---------- Filter & render ---------- */
const FIELDS=[['all','Semua informasi'],['judul','Judul'],['pengarang','Pengarang'],['tahun','Tahun terbit']];
const OPS=[['AND','AND'],['OR','OR'],['NOT','AND NOT']];
function populateFilters(){
  const cats = [...new Set(books.map(b=>b.kategori))].sort();
  [...new Set(books.map(b=>b.tahun))].filter(Boolean).sort((a,b)=>b-a).forEach(v=>{$('yearFrom').add(new Option(v,v));$('yearTo').add(new Option(v,v));});
  $('categoryChips').innerHTML = '';
  [['Semua buku',''],...cats.map(c=>[c,c])].forEach(([label,value])=>{
    const btn=document.createElement('button');btn.type='button';btn.className='category-chip'+(value===''?' active':'');btn.textContent=label;
    btn.addEventListener('click',()=>{activeChip=value;updateChipState();render();});
    $('categoryChips').appendChild(btn);
  });
  addRow();
}
function addRow(){
  const rows=$('sxRows'), first=rows.children.length===0;
  const row=document.createElement('div');row.className='sx-row'+(first?'':' has-op');
  let h='';
  if(!first)h+='<div class="sx-box"><label>Operator</label><select class="sx-op">'+OPS.map(o=>'<option value="'+o[0]+'">'+o[1]+'</option>').join('')+'</select></div>';
  h+='<div class="sx-box"><label>Cari dalam</label><select class="sx-field">'+FIELDS.map(f=>'<option value="'+f[0]+'">'+f[1]+'</option>').join('')+'</select></div>';
  h+='<div class="sx-box"><label>Cari buku</label><input type="search" class="sx-q" placeholder="Judul, pengarang, atau topik..."></div>';
  if(!first)h+='<button type="button" class="sx-rm" aria-label="Hapus kolom">×</button>';
  row.innerHTML=h;
  const rm=row.querySelector('.sx-rm');if(rm)rm.addEventListener('click',()=>{row.remove();render();});
  rows.appendChild(row);
}
function updateChipState(){
  [...$('categoryChips').children].forEach(btn=>btn.classList.toggle('active',(btn.textContent==='Semua buku'&&activeChip==='')||btn.textContent===activeChip));
}
const lc = s => String(s||'').toLocaleLowerCase('id');
function getRows(){
  return [...document.querySelectorAll('#sxRows .sx-row')].map(r=>({
    op:(r.querySelector('.sx-op')||{}).value||'AND',
    f:r.querySelector('.sx-field').value,
    q:lc(r.querySelector('.sx-q').value.trim())
  })).filter(r=>r.q);
}
function rowMatch(b,r){
  const hay=r.f==='all'?lc([b.judul,b.pengarang,b.tahun].join(' ')):lc(b[r.f]);
  return hay.includes(r.q);
}
function matchRows(b,rows){
  if(!rows.length)return true;
  let res=rowMatch(b,rows[0]);
  for(let i=1;i<rows.length;i++){
    const m=rowMatch(b,rows[i]);
    res=rows[i].op==='OR'?(res||m):rows[i].op==='NOT'?(res&&!m):(res&&m);
  }
  return res;
}
function getFiltered(){
  const rows=getRows(), useDate=!$('sxDates').hidden;
  const from=parseInt($('yearFrom').value,10), to=parseInt($('yearTo').value,10);
  let list=books.filter(b=>{
    const y=parseInt(b.tahun,10);
    return matchRows(b,rows)&&(!activeChip||b.kategori===activeChip)
      &&(!useDate||((isNaN(from)||y>=from)&&(isNaN(to)||y<=to)))
      &&(!lensMatches||lensMatches.has(b.id));
  });
  if(lensMatches)list.sort((a,b)=>lensMatches.get(b.id)-lensMatches.get(a.id));
  return list;
}
function render(){
  const list=getFiltered();resultsEl.innerHTML='';
  $('resultCount').textContent=`${list.length} BUKU DITEMUKAN`;
  emptyEl.style.display=list.length?'none':'block';resultsEl.style.display=list.length?'grid':'none';
  $('lensBanner').style.display=lensMatches?'flex':'none';
  if(lensMatches)$('lensBannerText').textContent='Hasil pencarian kamera: '+lensMatches.size+' buku yang cocok dengan foto.';
  list.forEach(b=>{
    const article=document.createElement('article');article.className='book-card';
    const badge=document.createElement('span');badge.className='book-category';badge.textContent=b.kategori;
    const info=document.createElement('div');info.className='book-info';
    const title=document.createElement('h3');title.textContent=b.judul;
    const author=document.createElement('p');author.className='author';author.textContent=b.pengarang;
    const meta=document.createElement('div');meta.className='book-meta';meta.style.marginTop='auto';meta.style.paddingTop='14px';
    const year=document.createElement('span');year.textContent='TAHUN '+b.tahun;
    const detail=document.createElement('button');detail.className='detail-link';detail.type='button';detail.textContent='Detail →';detail.addEventListener('click',()=>openDetail(b.id));
    meta.append(year,detail);info.append(title,author,meta);article.append(badge,info);resultsEl.appendChild(article);
  });
}
function resetForm(){
  $('sxRows').innerHTML='';addRow();
  $('yearFrom').value='';$('yearTo').value='';$('sxDates').hidden=true;
  activeChip='';updateChipState();
}
function resetFilters(){resetForm();lensMatches=null;render();}

/* ---------- Detail buku ---------- */
function openDetail(id){
  const b=books.find(item=>Number(item.id)===Number(id));if(!b)return;
  $('mdTitle').textContent=b.judul;$('mdAuthor').textContent='Oleh '+b.pengarang;
  $('mdInitial').textContent=(b.judul.trim().charAt(0)||'?').toUpperCase();
  $('mdSideCat').textContent=b.kategori;$('mdSideYear').textContent=b.tahun;
  $('mdKategori').textContent=b.kategori;
  $('mdTahun').textContent=b.tahun;
  $('mdStok').textContent=b.stok===null?'—':b.stok+' eksemplar';
  const cov=$('mdCover'),ini=$('mdInitial');
  if(b.cover){cov.src='uploads/cover/'+encodeURIComponent(b.cover);cov.alt='Sampul '+b.judul;cov.style.display='block';ini.style.display='none';}
  else{cov.removeAttribute('src');cov.style.display='none';ini.style.display='block';}
  const others=books.filter(x=>x.pengarang===b.pengarang&&x.id!==b.id);
  const rel=$('mdRelated');rel.innerHTML='';
  others.forEach(x=>{const btn=document.createElement('button');btn.type='button';btn.className='related-btn';btn.textContent=x.judul+' ('+x.tahun+')';btn.addEventListener('click',()=>openDetail(x.id));rel.appendChild(btn);});
  $('mdRelatedWrap').style.display=others.length?'block':'none';
  const rest=books.filter(x=>x.id!==b.id&&x.pengarang!==b.pengarang).sort(()=>Math.random()-.5).slice(0,6);
  const og=$('mdOther');og.innerHTML='';
  rest.forEach(x=>{
    const it=document.createElement('button');it.type='button';it.className='other-item';
    let th;
    if(x.cover){th=document.createElement('img');th.className='other-thumb';th.src='uploads/cover/'+encodeURIComponent(x.cover);th.alt='Sampul '+x.judul;th.loading='lazy';}
    else{th=document.createElement('div');th.className='other-thumb empty';th.textContent=(x.judul.trim().charAt(0)||'?').toUpperCase();}
    const t=document.createElement('div');t.className='other-title';t.textContent=x.judul;
    const a=document.createElement('div');a.className='other-author';a.textContent=x.pengarang;
    it.append(th,t,a);it.addEventListener('click',()=>{openDetail(x.id);document.querySelector('.modal').scrollTop=0;});
    og.appendChild(it);
  });
  $('mdOtherWrap').style.display=rest.length?'block':'none';
  $('modalBackdrop').style.display='flex';$('closeModal').focus();
}
function closeModal(){$('modalBackdrop').style.display='none';}

/* ---------- Pencarian kamera (OCR) ---------- */
let lensStream=null, busy=false;
const setStatus = t => $('lensStatus').textContent=t;
const STOP = new Set(['dan','yang','dari','dengan','untuk','pada','oleh','the','and','for','with','book','buku']);
function tokens(s){
  return String(s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .split(/[^a-z0-9]+/).filter(w=>w.length>=3&&!STOP.has(w));
}
function matchText(text){
  const seen=new Set(tokens(text));
  return books.map(b=>{
    const tt=tokens(b.judul), at=tokens(b.pengarang);
    const th=tt.filter(w=>seen.has(w)).length, ah=at.filter(w=>seen.has(w)).length;
    const score=(tt.length?th/tt.length:0)*2+(at.length?ah/at.length:0);
    return {id:b.id,score,hits:th+ah};
  }).filter(x=>x.hits>0&&x.score>=0.5).sort((a,b)=>b.score-a.score).slice(0,12);
}
function loadTesseract(){
  return new Promise((res,rej)=>{
    if(window.Tesseract)return res();
    const s=document.createElement('script');
    s.src='https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js';
    s.onload=res;s.onerror=()=>rej(new Error('Gagal memuat OCR (cek koneksi internet).'));
    document.head.appendChild(s);
  });
}
async function startCamera(){
  setStatus('');
  if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){
    setStatus('Kamera langsung butuh HTTPS. Kamu tetap bisa pakai "Pilih dari galeri".');return;
  }
  try{
    lensStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});
    $('lensVideo').srcObject=lensStream;
  }catch(e){
    setStatus('Kamera tidak bisa diakses. Izinkan akses kamera, atau pakai "Pilih dari galeri".');
  }
}
function stopCamera(){
  if(lensStream)lensStream.getTracks().forEach(t=>t.stop());
  lensStream=null;$('lensVideo').srcObject=null;
}
function openLens(){$('lensBackdrop').style.display='flex';startCamera();}
function closeLens(){stopCamera();$('lensBackdrop').style.display='none';busy=false;lensButtons(false);}
function lensButtons(disabled){$('lensShot').disabled=disabled;$('lensFile').disabled=disabled;}
async function recognize(source){
  if(busy)return;busy=true;lensButtons(true);
  try{
    setStatus('Memuat pembaca teks…');
    await loadTesseract();
    const {data}=await Tesseract.recognize(source,'ind+eng',{logger:m=>{
      if(m.status==='recognizing text')setStatus('Membaca teks… '+Math.round(m.progress*100)+'%');
    }});
    const found=matchText(data.text||'');
    if(!found.length){
      setStatus('Belum ada buku yang cocok. Coba foto lebih dekat & terang, pastikan judulnya jelas.');
      return;
    }
    lensMatches=new Map(found.map(x=>[x.id,x.score]));
    resetForm();
    closeLens();render();
    $('results').scrollIntoView({behavior:'smooth',block:'start'});
  }catch(e){
    setStatus(e.message||'Terjadi kesalahan saat membaca gambar.');
  }finally{busy=false;lensButtons(false);}
}
function shoot(){
  const v=$('lensVideo');
  if(!v.videoWidth){setStatus('Kamera belum siap. Gunakan "Pilih dari galeri" atau coba lagi.');return;}
  const c=$('lensCanvas'),scale=Math.min(1,1280/v.videoWidth);
  c.width=Math.round(v.videoWidth*scale);c.height=Math.round(v.videoHeight*scale);
  c.getContext('2d').drawImage(v,0,0,c.width,c.height);
  recognize(c);
}

/* ---------- Event ---------- */
$('searchForm').addEventListener('submit',e=>{e.preventDefault();lensMatches=null;render();});
$('sxRows').addEventListener('input',()=>{lensMatches=null;render();});
$('sxRows').addEventListener('change',()=>{lensMatches=null;render();});
$('yearFrom').addEventListener('change',render);$('yearTo').addEventListener('change',render);
$('addField').addEventListener('click',()=>{addRow();const q=document.querySelectorAll('#sxRows .sx-q');q[q.length-1].focus();});
$('addDate').addEventListener('click',()=>{$('sxDates').hidden=false;render();});
$('removeDate').addEventListener('click',()=>{$('yearFrom').value='';$('yearTo').value='';$('sxDates').hidden=true;render();});
$('resetBtn').addEventListener('click',resetFilters);$('emptyReset').addEventListener('click',resetFilters);
$('lensClear').addEventListener('click',()=>{lensMatches=null;render();});
$('lensOpen').addEventListener('click',openLens);
$('lensClose').addEventListener('click',closeLens);
$('lensBackdrop').addEventListener('click',e=>{if(e.target===$('lensBackdrop'))closeLens();});
$('lensShot').addEventListener('click',shoot);
$('lensFile').addEventListener('change',e=>{const f=e.target.files[0];if(f)recognize(f);e.target.value='';});
$('closeModal').addEventListener('click',closeModal);
$('modalBackdrop').addEventListener('click',e=>{if(e.target===$('modalBackdrop'))closeModal();});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();closeLens();}});
populateFilters();render();
</script>
</body>
</html>
