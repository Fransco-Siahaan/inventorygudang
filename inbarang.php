<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

$q     = trim($_GET['q'] ?? '');
$where = search_where(COLS_TAMBAH, $q);
$total = (int) db_scalar($conn, 'SELECT COUNT(*) FROM tambah' . $where['sql'], $where['types'], $where['params']);
$pg    = paginate($total, PER_PAGE_LIST, page_param());
$rows  = db_fetch_all(
    $conn,
    'SELECT * FROM tambah' . $where['sql'] . ' ORDER BY tanggal DESC, id DESC LIMIT ? OFFSET ?',
    $where['types'] . 'ii',
    array_merge($where['params'], [$pg['per'], $pg['offset']])
);

$extra   = $q !== '' ? ['q' => $q] : [];
$kembali = basename(__FILE__) . '?' . http_build_query(array_merge($extra, ['page' => $pg['page']]));

render_head('Riwayat Tambah Stok', 'riwayat_tambah', 'Semua penambahan stok yang pernah dicatat');
?>

<section class="card">
    <div class="toolbar">
        <form class="search" method="get" action="" role="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input class="input" type="search" name="q" value="<?= e($q) ?>"
                   placeholder="Cari kode, nama, supplier, tanggal…" aria-label="Cari riwayat tambah">
        </form>

        <div class="actions-right">
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline" href="inbarang.php">Reset</a>
            <?php endif; ?>
            <form method="post" action="cetakpdfinbarang.php" target="_blank">
                <input type="hidden" name="search_term" value="<?= e($q) ?>">
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-file-pdf"></i> PDF</button>
            </form>
            <form method="post" action="cetakexcelinbarang.php">
                <input type="hidden" name="search_term" value="<?= e($q) ?>">
                <button class="btn btn-green" type="submit"><i class="fa-solid fa-file-excel"></i> Excel</button>
            </form>
        </div>
    </div>

    <div class="result-info">
        <?php if ($total > 0): ?>
            Menampilkan <b><?= angka(($pg['page'] - 1) * $pg['per'] + 1) ?>–<?= angka(min($pg['page'] * $pg['per'], $total)) ?></b>
            dari <b><?= angka($total) ?></b> transaksi<?= $q !== '' ? ' untuk "' . e($q) . '"' : '' ?>
        <?php else: ?>
            Tidak ada hasil<?= $q !== '' ? ' untuk "' . e($q) . '"' : '' ?>
        <?php endif; ?>
    </div>

    <?php if (!$rows): ?>
        <div class="empty">
            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
            <b>Belum ada riwayat penambahan</b>
            Transaksi <i>Tambah Stok</i> akan tercatat di sini.
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-top:12px">
            <table class="table">
                <thead>
                    <tr>
                        <th class="ctr">No</th>
                        <th>Barang</th>
                        <th>Supplier</th>
                        <th>Tanggal</th>
                        <th class="num">Jumlah</th>
                                                <th class="num">Total</th>
                        <th class="col-lg">Keterangan</th>
                        <th class="ctr">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = $pg['offset'] + 1; foreach ($rows as $r): ?>
                        <tr>
                            <td class="ctr"><?= $no++ ?></td>
                            <td>
                                <span class="name"><?= e($r['nmbarang']) ?></span>
                                <span class="code"><?= e($r['kdbarang']) ?></span>
                            </td>
                            <td><?= e($r['np']) ?></td>
                            <td style="white-space:nowrap"><?= e(tgl_indo($r['tanggal'])) ?></td>
                            <td class="num">
                                <span class="badge in"><i class="fa-solid fa-arrow-down"></i> <?= e($r['satuan']) ?></span>
                            </td>
                                                        <td class="num"><?= rupiah($r['totalharga']) ?></td>
                            <td class="wrap-text col-lg"><?= e($r['ket']) ?></td>
                            <td class="ctr">
                                <form method="post" action="hapusdatainbarang.php"
                                      data-confirm="Hapus riwayat tambah “<?= e($r['nmbarang']) ?>” tanggal <?= e(tgl_indo($r['tanggal'])) ?>? Stok barang tidak berubah otomatis.">
                                    <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                    <input type="hidden" name="redirect" value="<?= e($kembali) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" aria-label="Hapus riwayat ini">
                                        <i class="fa-solid fa-trash-can"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pager($pg, $extra) ?>
    <?php endif; ?>
</section>

<?php render_foot(); ?>
