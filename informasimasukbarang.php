<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

$q       = trim($_GET['q'] ?? '');
$where   = search_where(COLS_INPUT, $q);
$total   = (int) db_scalar($conn, 'SELECT COUNT(*) FROM input' . $where['sql'], $where['types'], $where['params']);
$pg      = paginate($total, PER_PAGE_LIST, page_param());
$rows    = db_fetch_all(
    $conn,
    'SELECT * FROM input' . $where['sql'] . ' ORDER BY nomor DESC LIMIT ? OFFSET ?',
    $where['types'] . 'ii',
    array_merge($where['params'], [$pg['per'], $pg['offset']])
);

$extra   = $q !== '' ? ['q' => $q] : [];
$kembali = basename(__FILE__) . '?' . http_build_query(array_merge($extra, ['page' => $pg['page']]));

render_head('Daftar Stok', 'stok', 'Lihat stok, detail, dan riwayat setiap barang');
?>

<section class="card">
    <div class="toolbar">
        <form class="search" method="get" action="" role="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input class="input" type="search" name="q" id="q" value="<?= e($q) ?>"
                   placeholder="Cari kode, nama, pengirim, tanggal…" aria-label="Cari barang">
        </form>

        <div class="actions-right">
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline" href="informasimasukbarang.php">Reset</a>
            <?php endif; ?>
            <form method="post" action="cetakpdf.php" target="_blank">
                <input type="hidden" name="search_term" value="<?= e($q) ?>">
                <button class="btn btn-danger" type="submit"><i class="fa-solid fa-file-pdf"></i> PDF</button>
            </form>
            <form method="post" action="cetakexcel.php">
                <input type="hidden" name="search_term" value="<?= e($q) ?>">
                <button class="btn btn-green" type="submit"><i class="fa-solid fa-file-excel"></i> Excel</button>
            </form>
        </div>
    </div>

    <div class="result-info">
        <?php if ($total > 0): ?>
            Menampilkan <b><?= angka(($pg['page'] - 1) * $pg['per'] + 1) ?>–<?= angka(min($pg['page'] * $pg['per'], $total)) ?></b>
            dari <b><?= angka($total) ?></b> barang<?= $q !== '' ? ' untuk "' . e($q) . '"' : '' ?>
        <?php else: ?>
            Tidak ada hasil<?= $q !== '' ? ' untuk "' . e($q) . '"' : '' ?>
        <?php endif; ?>
    </div>

    <?php if (!$rows): ?>
        <div class="empty">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <b><?= $q !== '' ? 'Barang tidak ditemukan' : 'Belum ada barang' ?></b>
            <?= $q !== '' ? 'Coba kata kunci lain atau reset pencarian.' : 'Mulai dengan menginput barang baru.' ?>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-top:12px">
            <table class="table">
                <thead>
                    <tr>
                        <th class="ctr">No</th>
                        <th>Barang</th>
                        <th>Pengirim</th>
                        <th>Tanggal</th>
                        <th class="num">Stok</th>
                        <th class="num">Total Nilai</th>
                        <th class="col-lg">Keterangan</th>
                        <th class="ctr">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = $pg['offset'] + 1; foreach ($rows as $r):
                        [$jml, $unit] = split_satuan($r['satuan']);
                        $stokKurang = (int) $r['jumlah'] <= LOW_STOCK;
                        $pakai = json_encode($r, JSON_UNESCAPED_UNICODE);
                    ?>
                        <tr>
                            <td class="ctr"><?= $no++ ?></td>
                            <td>
                                <span class="name"><?= e($r['namabarang']) ?></span>
                                <span class="code"><?= e($r['kdbarang']) ?></span>
                            </td>
                            <td><?= e($r['np']) ?></td>
                            <td style="white-space:nowrap"><?= e(tgl_indo($r['tanggal'])) ?></td>
                            <td class="num">
                                <span class="<?= $stokKurang ? 'stock-low' : '' ?>"><?= angka($r['jumlah']) ?></span>
                                <span class="muted"><?= e($unit) ?></span>
                                <?php if ($stokKurang): ?>
                                    <span class="badge low" style="margin-left:6px">Menipis</span>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= rupiah($r['totalharga']) ?></td>
                            <td class="wrap-text col-lg"><?= e($r['ket']) ?></td>
                            <td class="ctr">
                                <div class="actions">
                                    <button type="button" class="btn btn-sm btn-outline btnDetail"
                                            data-row="<?= e($pakai) ?>" title="Lihat detail" aria-label="Detail <?= e($r['namabarang']) ?>">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </button>
                                    <form method="post" action="hapusdata.php"
                                          data-confirm="Hapus barang “<?= e($r['namabarang']) ?>” dari daftar stok? Riwayat tambah dan keluar tetap tersimpan.">
                                        <input type="hidden" name="nomor" value="<?= e($r['nomor']) ?>">
                                        <input type="hidden" name="redirect" value="<?= e($kembali) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" aria-label="Hapus <?= e($r['namabarang']) ?>">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render_pager($pg, $extra) ?>
    <?php endif; ?>
</section>

<!-- Modal detail barang -->
<div class="modal-backdrop" id="detailModal" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
    <div class="modal">
        <div class="modal-head">
            <h2 id="detailTitle">Detail Barang</h2>
            <button type="button" class="icon-btn" data-close="detailModal" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <dl class="kv" id="detailKv"></dl>

            <div class="row between">
                <div class="tabs" role="tablist" id="detailTabs">
                    <button type="button" role="tab" class="active" data-tab="semua">Semua transaksi</button>
                    <button type="button" role="tab" data-tab="tambah">Barang masuk</button>
                    <button type="button" role="tab" data-tab="keluar">Barang keluar</button>
                </div>
                <form method="post" action="cetakexcelsemuabarang.php" id="exportItemForm">
                    <input type="hidden" name="search_term" value="">
                    <input type="hidden" name="kdbarang_spesifik" id="exportKode" value="">
                    <button class="btn btn-sm btn-green" type="submit"><i class="fa-solid fa-file-excel"></i> Export Excel</button>
                </form>
            </div>

            <div id="riwayatBox" class="table-wrap"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const kv = document.getElementById('detailKv');
    const box = document.getElementById('riwayatBox');
    let current = { data: null, tab: 'semua', tambah: null, keluar: null };

    function addKV(label, value) {
        const dt = document.createElement('dt');
        dt.textContent = label;
        const dd = document.createElement('dd');
        dd.textContent = value;
        kv.append(dt, dd);
    }

    function openDetail(row) {
        current = { data: row, tab: 'semua', tambah: null, keluar: null };
        kv.innerHTML = '';
        addKV('Nomor', row.nomor);
        addKV('Kode barang', row.kdbarang);
        addKV('Nama barang', row.namabarang);
        addKV('Pengirim', row.np);
        addKV('Tanggal', row.tanggal);
        addKV('Satuan', row.satuan);
        addKV('Harga satuan', rp(row.harga));
        addKV('Total nilai', rp(row.totalharga));
        addKV('Keterangan', row.ket || '–');

        document.getElementById('exportKode').value = row.kdbarang;
        setTab('semua');
        openModal('detailModal');
        loadRiwayat(row.kdbarang);
    }

    async function loadRiwayat(kode) {
        box.innerHTML = '';
        renderMessage('Memuat riwayat…');
        try {
            const [tambah, keluar] = await Promise.all([
                fetch(`get_riwayat.php?kdbarang=${encodeURIComponent(kode)}&type=tambah`).then(r => r.json()),
                fetch(`get_riwayat.php?kdbarang=${encodeURIComponent(kode)}&type=keluar`).then(r => r.json())
            ]);
            current.tambah = Array.isArray(tambah) ? tambah : [];
            current.keluar = Array.isArray(keluar) ? keluar : [];
            renderTab();
        } catch (err) {
            renderMessage('Gagal memuat riwayat. Coba lagi.');
        }
    }

    function renderMessage(text) {
        box.innerHTML = '';
        const p = document.createElement('p');
        p.className = 'muted';
        p.style.padding = '18px 4px';
        p.textContent = text;
        box.appendChild(p);
    }

    function setTab(tab) {
        current.tab = tab;
        document.querySelectorAll('#detailTabs button').forEach((b) => {
            const on = b.dataset.tab === tab;
            b.classList.toggle('active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        if (current.tambah && current.keluar) renderTab();
    }

    function renderTab() {
        const t = current.tab;
        let items = [];
        if (t === 'semua') {
            items = [
                ...current.tambah.map(r => ({ ...r, jenis: 'MASUK' })),
                ...current.keluar.map(r => ({ ...r, jenis: 'KELUAR' }))
            ].sort((a, b) => (b.tanggal > a.tanggal ? 1 : b.tanggal < a.tanggal ? -1 : 0));
        } else {
            items = (t === 'tambah' ? current.tambah : current.keluar).map(r => ({ ...r, jenis: t === 'tambah' ? 'MASUK' : 'KELUAR' }));
        }

        if (!items.length) {
            renderMessage('Belum ada riwayat untuk tampilan ini.');
            return;
        }

        const table = document.createElement('table');
        table.className = 'table';
        const thead = document.createElement('thead');
        const headRow = document.createElement('tr');
        const heads = t === 'semua'
            ? ['Jenis', 'Nomor', 'Tanggal', 'Jumlah', 'Keterangan']
            : ['Nomor', 'Tanggal', 'Jumlah', 'Keterangan'];
        heads.forEach((h) => {
            const th = document.createElement('th');
            th.textContent = h;
            headRow.appendChild(th);
        });
        thead.appendChild(headRow);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        items.forEach((r) => {
            const tr = document.createElement('tr');
            const cells = [];
            if (t === 'semua') {
                const span = document.createElement('span');
                span.className = 'badge ' + (r.jenis === 'MASUK' ? 'in' : 'out');
                span.textContent = r.jenis === 'MASUK' ? 'Masuk' : 'Keluar';
                cells.push(span);
            }
            cells.push(r.nomor, formatTgl(r.tanggal), `${idn(r.jumlah)} ${unitOf(r.satuan)}`.trim(), r.ket || '–');
            cells.forEach((c) => {
                const td = document.createElement('td');
                if (c instanceof Node) td.appendChild(c); else td.textContent = c;
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        box.innerHTML = '';
        box.appendChild(table);
    }

    // "3 pcs" -> "pcs"
    function unitOf(satuan) {
        return (satuan || '').replace(/^\s*\d+\s*/, '').trim();
    }

    function formatTgl(s) {
        if (!s) return '–';
        const [y, m, d] = s.split('-');
        return `${d}/${m}/${y}`;
    }

    document.querySelectorAll('#detailTabs button').forEach((b) => {
        b.addEventListener('click', () => setTab(b.dataset.tab));
    });

    document.querySelectorAll('.btnDetail').forEach((btn) => {
        btn.addEventListener('click', () => openDetail(JSON.parse(btn.dataset.row)));
    });

});
</script>

<?php render_foot(); ?>
