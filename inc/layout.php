<?php
// Layout bersama: sidebar, topbar, dan footer.
// Pemakaian:
//   render_head('Judul', 'kunci_menu_aktif', 'Subjudul opsional');
//   ... isi halaman ...
//   render_foot();

function nav_groups(): array
{
    return [
        'Utama' => [
            ['index.php', 'Dashboard', 'fa-gauge-high', 'dashboard'],
        ],
        'Transaksi' => [
            ['masuk.php', 'Input Barang Baru', 'fa-box-open', 'masuk'],
            ['nambahbarang.php', 'Tambah Stok', 'fa-circle-plus', 'tambah'],
            ['output.php', 'Pengeluaran', 'fa-truck-fast', 'output'],
        ],
        'Data & Riwayat' => [
            ['informasimasukbarang.php', 'Daftar Stok', 'fa-warehouse', 'stok'],
            ['inbarang.php', 'Riwayat Tambah', 'fa-clock-rotate-left', 'riwayat_tambah'],
            ['riwayatkurangbarang.php', 'Riwayat Keluar', 'fa-file-export', 'riwayat_keluar'],
        ],
    ];
}

function render_head(string $title, string $active, string $subtitle = ''): void
{
    $flash = flash_all();
    $flashJson = json_encode($flash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · Torsada Gudang</title>
    <link rel="icon" href="gambar/logo.png">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar" aria-label="Menu utama">
        <div class="brand">
            <img src="gambar/logo.png" alt="Logo Torsada">
            <div>
                <strong>Inventory Gudang</strong>
                <span>PT. Argatek Torsada Guna</span>
            </div>
        </div>

        <nav>
            <?php foreach (nav_groups() as $grup => $items): ?>
                <div class="nav-group"><?= e($grup) ?></div>
                <?php foreach ($items as [$href, $label, $icon, $key]): ?>
                    <a class="nav-link<?= $key === $active ? ' active' : '' ?>"
                       href="<?= e($href) ?>"
                       <?= $key === $active ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i>
                        <span><?= e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-foot">v2.0 · Sistem Inventory</div>
    </aside>
    <div class="backdrop" id="backdrop"></div>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn menu-btn" id="btnMenu" type="button" aria-label="Buka menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="page-title">
                <h1><?= e($title) ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p><?= e($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <div class="topbar-date">
                <i class="fa-regular fa-calendar"></i>
                <?= e(tgl_indo(date('Y-m-d'))) ?>
            </div>
        </header>

        <main class="content">
    <script>window.FLASH = <?= $flashJson ?>;</script>
    <?php
}

function render_foot(): void
{
    ?>
        </main>
    </div>
</div>

<!-- Dialog konfirmasi (dipakai oleh form ber-atribut data-confirm) -->
<div class="modal-backdrop" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="modal sm">
        <div class="modal-head">
            <h2 id="confirmTitle">Konfirmasi</h2>
        </div>
        <div class="modal-body">
            <p id="confirmText" class="confirm-text"></p>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-outline" data-close="confirmModal">Batal</button>
            <button type="button" class="btn btn-danger" id="confirmOk">
                <i class="fa-solid fa-trash-can"></i> Ya, lanjutkan
            </button>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
    <?php
}
