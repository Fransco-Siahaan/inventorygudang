<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

// Ringkasan angka utama
$ringkasan = db_one(
    $conn,
    'SELECT COUNT(*) AS jenis,
            COALESCE(SUM(jumlah), 0) AS stok,
            COALESCE(SUM(totalharga), 0) AS nilai
     FROM input'
);

$bulanIni = db_one(
    $conn,
    "SELECT COALESCE(SUM(jumlah), 0) AS jml, COALESCE(SUM(totalharga), 0) AS nilai
     FROM tambah WHERE DATE_FORMAT(tanggal, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
);
$keluarBulanIni = db_one(
    $conn,
    "SELECT COALESCE(SUM(jumlah), 0) AS jml, COALESCE(SUM(totalharga), 0) AS nilai
     FROM keluar WHERE DATE_FORMAT(tanggal, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
);

// Barang yang stoknya menipis
$menipis = db_fetch_all(
    $conn,
    'SELECT kdbarang, namabarang, jumlah, satuan FROM input
     WHERE jumlah <= ? ORDER BY jumlah ASC, namabarang ASC LIMIT 6',
    'i',
    [LOW_STOCK]
);
$jumlahMenipis = (int) db_scalar($conn, 'SELECT COUNT(*) FROM input WHERE jumlah <= ?', 'i', [LOW_STOCK]);

// Aktivitas terbaru (gabungan masuk & keluar)
$aktivitas = db_fetch_all(
    $conn,
    "SELECT * FROM (
        SELECT 'MASUK' AS jenis, kdbarang, nmbarang, jumlah, tanggal FROM tambah
        UNION ALL
        SELECT 'KELUAR' AS jenis, kdbarang, nmbarang, jumlah, tanggal FROM keluar
     ) t ORDER BY tanggal DESC LIMIT 8"
);

render_head('Dashboard', 'dashboard', 'Ringkasan stok dan aktivitas gudang hari ini');
?>

<section class="stats" aria-label="Ringkasan">
    <div class="card stat">
        <div class="stat-ico"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div>
            <div class="label">Jenis Barang</div>
            <div class="value"><?= angka($ringkasan['jenis']) ?></div>
            <div class="hint">Kode barang terdaftar</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-ico"><i class="fa-solid fa-cubes"></i></div>
        <div>
            <div class="label">Total Unit di Gudang</div>
            <div class="value"><?= angka($ringkasan['stok']) ?></div>
            <div class="hint">Jumlah seluruh stok</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-ico"><i class="fa-solid fa-sack-dollar"></i></div>
        <div>
            <div class="label">Nilai Persediaan</div>
            <div class="value"><?= rupiah($ringkasan['nilai']) ?></div>
            <div class="hint">Berdasarkan harga barang</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-ico amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
            <div class="label">Stok Menipis</div>
            <div class="value"><?= angka($jumlahMenipis) ?></div>
            <div class="hint">Stok ≤ <?= LOW_STOCK ?> satuan</div>
        </div>
    </div>
</section>

<section class="quick" aria-label="Aksi cepat" style="margin-bottom:22px">
    <a href="masuk.php">
        <i class="fa-solid fa-box-open"></i>
        <b>Input Barang Baru</b>
        <span>Daftarkan kode barang yang belum ada</span>
    </a>
    <a href="nambahbarang.php">
        <i class="fa-solid fa-circle-plus"></i>
        <b>Tambah Stok</b>
        <span>Catat barang masuk untuk stok yang sudah ada</span>
    </a>
    <a href="output.php">
        <i class="fa-solid fa-truck-fast"></i>
        <b>Pengeluaran</b>
        <span>Catat barang keluar dari gudang</span>
    </a>
</section>

<div class="grid-2">
    <section class="card" aria-labelledby="h-aktivitas">
        <div class="card-head">
            <h2 id="h-aktivitas">Aktivitas Terbaru</h2>
            <a class="btn btn-ghost btn-sm" href="inbarang.php">Lihat semua riwayat</a>
        </div>
        <?php if (!$aktivitas): ?>
            <div class="empty">
                <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                <b>Belum ada aktivitas</b>
                Transaksi tambah atau keluar akan tampil di sini.
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Barang</th>
                            <th class="num">Jumlah</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aktivitas as $a): ?>
                            <tr>
                                <td>
                                    <?php if ($a['jenis'] === 'MASUK'): ?>
                                        <span class="badge in"><i class="fa-solid fa-arrow-down"></i> Masuk</span>
                                    <?php else: ?>
                                        <span class="badge out"><i class="fa-solid fa-arrow-up"></i> Keluar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="name"><?= e($a['nmbarang']) ?></span>
                                    <span class="sub"><?= e($a['kdbarang']) ?></span>
                                </td>
                                <td class="num"><?= angka($a['jumlah']) ?></td>
                                <td><?= e(tgl_indo($a['tanggal'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="h-menipis">
        <div class="card-head">
            <h2 id="h-menipis">Perlu Perhatian</h2>
            <a class="btn btn-ghost btn-sm" href="informasimasukbarang.php">Daftar stok</a>
        </div>
        <?php if (!$menipis): ?>
            <div class="empty">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <b>Semua stok aman</b>
                Tidak ada barang dengan stok menipis.
            </div>
        <?php else: ?>
            <ul style="list-style:none;margin:0;padding:6px 0">
                <?php foreach ($menipis as $m): ?>
                    <li style="display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px 20px;border-bottom:1px solid var(--line)">
                        <div style="min-width:0">
                            <div class="name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($m['namabarang']) ?></div>
                            <span class="code"><?= e($m['kdbarang']) ?></span>
                        </div>
                        <span class="badge low"><?= e($m['jumlah']) ?> <?= e(split_satuan($m['satuan'])[1]) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($jumlahMenipis > count($menipis)): ?>
                <div class="muted" style="padding:12px 20px;font-size:13px">
                    dan <?= angka($jumlahMenipis - count($menipis)) ?> barang lainnya stoknya menipis.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php render_foot(); ?>
