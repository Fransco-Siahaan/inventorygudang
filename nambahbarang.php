<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

$barang = db_fetch_all(
    $conn,
    'SELECT kdbarang, namabarang, jumlah, satuan, harga FROM input ORDER BY kdbarang ASC'
);

render_head('Tambah Stok', 'tambah', 'Catat penambahan stok untuk barang yang sudah terdaftar');
?>

<?php if (!$barang): ?>
    <section class="card">
        <div class="empty">
            <i class="fa-solid fa-box-open" aria-hidden="true"></i>
            <b>Belum ada barang terdaftar</b>
            Daftarkan barang terlebih dahulu di menu <a href="masuk.php">Input Barang Baru</a>.
        </div>
    </section>
<?php else: ?>
<div class="grid-2">
    <section class="card" aria-labelledby="h-form">
        <div class="card-head">
            <h2 id="h-form">Form Tambah Stok</h2>
            <span class="sub">Harga mengikuti harga satuan barang</span>
        </div>

        <form id="formTambah" class="card-body" action="formnambahbarang.php" method="post" novalidate>
            <div class="form-grid">
                <div class="field full" data-field="kdbarang">
                    <label for="kdbarang" class="req">Pilih Barang</label>
                    <select class="select" name="kdbarang" id="kdbarang" required>
                        <option value="">Pilih kode barang…</option>
                        <?php foreach ($barang as $b): ?>
                            <option value="<?= e($b['kdbarang']) ?>"
                                    data-nama="<?= e($b['namabarang']) ?>"
                                    data-stok="<?= e($b['jumlah']) ?>"
                                    data-satuan="<?= e(split_satuan($b['satuan'])[1]) ?>"
                                    data-harga="<?= e($b['harga']) ?>">
                                <?= e($b['kdbarang']) ?> — <?= e($b['namabarang']) ?> (stok <?= angka($b['jumlah']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="err">Pilih barang terlebih dahulu.</span>
                </div>

                <div class="field" data-field="nmbarang">
                    <label for="nmbarang">Nama Barang</label>
                    <input class="input" type="text" name="nmbarang" id="nmbarang" readonly tabindex="-1" placeholder="Otomatis">
                </div>

                <div class="field" data-field="supplier">
                    <label for="supplier" class="req">Supplier / Pemasok</label>
                    <input class="input" type="text" name="supplier" id="supplier" required placeholder="Nama pemasok">
                    <span class="err">Nama supplier wajib diisi.</span>
                </div>

                <div class="field" data-field="jumlah">
                    <label for="jumlah" class="req">Jumlah Tambahan</label>
                    <input class="input" type="number" name="jumlah" id="jumlah" min="1" step="1" inputmode="numeric" required placeholder="0">
                    <span class="err">Jumlah minimal 1.</span>
                </div>

                <div class="field" data-field="harga">
                    <label for="harga">Harga Satuan</label>
                    <div class="input-money">
                        <span>Rp</span>
                        <input class="input" type="text" id="harga" readonly tabindex="-1" placeholder="0">
                    </div>
                </div>

                <div class="field" data-field="tanggal">
                    <label for="tanggal" class="req">Tanggal Masuk</label>
                    <input class="input" type="date" name="tanggal" id="tanggal" value="<?= e(date('Y-m-d')) ?>" data-today="<?= e(date('Y-m-d')) ?>" required>
                    <span class="err">Tanggal wajib diisi.</span>
                </div>

                <div class="field" data-field="ket">
                    <label for="ket" class="req">Keterangan</label>
                    <input class="input" type="text" name="ket" id="ket" required placeholder="Contoh: restock bulanan">
                    <span class="err">Keterangan wajib diisi.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="reset" class="btn btn-outline" id="btnReset"><i class="fa-solid fa-rotate-left"></i> Kosongkan</button>
                <button type="submit" class="btn btn-green" id="btnSubmit"><i class="fa-solid fa-circle-plus"></i> Tambah Stok</button>
            </div>
        </form>
    </section>

    <aside class="summary" aria-label="Ringkasan">
        <section class="summary-box" aria-live="polite">
            <div class="summary-row"><span>Stok saat ini</span><b id="sumAwal">–</b></div>
            <div class="summary-row"><span>Ditambahkan</span><b id="sumTambah">–</b></div>
            <div class="summary-row total"><span>Stok setelah tambah</span><b id="sumAkhir">–</b></div>
            <div class="summary-row"><span>Nilai tambahan</span><b id="sumNilai">Rp 0</b></div>
        </section>
        <div class="notice info" id="infoPilih">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>Pilih barang untuk melihat stok saat ini dan simulasi stok setelah ditambah.</span>
        </div>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formTambah');
    const btnSubmit = document.getElementById('btnSubmit');
    const $ = (id) => document.getElementById(id);

    function selected() {
        const opt = $('kdbarang').selectedOptions[0];
        return opt && opt.value ? opt.dataset : null;
    }

    function refresh() {
        const d = selected();
        const jumlah = parseInt($('jumlah').value, 10) || 0;
        if (!d) {
            $('nmbarang').value = '';
            $('harga').value = '';
            ['sumAwal', 'sumTambah', 'sumAkhir'].forEach((id) => $(id).textContent = '–');
            $('sumNilai').textContent = rp(0);
            $('infoPilih').classList.remove('hidden');
            return;
        }
        const stok = parseInt(d.stok, 10) || 0;
        const harga = parseFloat(d.harga) || 0;
        const satuan = d.satuan || '';
        $('nmbarang').value = d.nama;
        $('harga').value = idn(harga);
        $('sumAwal').textContent = `${idn(stok)} ${satuan}`.trim();
        $('sumTambah').textContent = jumlah ? `+ ${idn(jumlah)} ${satuan}`.trim() : '–';
        $('sumAkhir').textContent = `${idn(stok + jumlah)} ${satuan}`.trim();
        $('sumNilai').textContent = rp(jumlah * harga);
        $('infoPilih').classList.add('hidden');
    }

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();

    // Validasi per field. Field kosong baru ditandai saat submit (tidak mengganggu saat pengguna masih mengisi).
    function fieldOk(el) {
        const kosong = (el.tagName === 'TEXTAREA' || el.type === 'text') && el.value.trim() === '';
        return el.checkValidity() && !kosong;
    }
    function validateField(el) {
        const f = el.closest('.field');
        const ok = fieldOk(el);
        if (f) f.classList.toggle('invalid', !ok);
        return ok;
    }
    function validate() {
        let ok = true, first = null;
        form.querySelectorAll('[required]').forEach((el) => {
            if (!validateField(el)) { ok = false; first = first || el; }
        });
        if (first) first.focus();
        return ok;
    }
    // Tandai saat pengguna meninggalkan field yang sudah berisi nilai tidak valid
    form.addEventListener('focusout', (e) => {
        if (e.target.hasAttribute && e.target.hasAttribute('required') && e.target.value !== '') validateField(e.target);
    });
    // Hilangkan tanda error begitu nilainya sudah benar
    form.addEventListener('input', (e) => {
        if (e.target.closest && e.target.closest('.field.invalid') && fieldOk(e.target)) {
            e.target.closest('.field').classList.remove('invalid');
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!validate()) {
            toast('warning', 'Lengkapi kolom yang bertanda merah terlebih dahulu.');
            return;
        }
        setLoading(btnSubmit, true);
        try {
            const res = await fetch('formnambahbarang.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.href = 'nambahbarang.php';
                return;
            }
            toast('error', data.message || 'Gagal menambah stok.');
        } catch (err) {
            toast('error', 'Tidak dapat terhubung ke server. Periksa koneksi Anda.');
        } finally {
            setLoading(btnSubmit, false);
        }
    });

    $('btnReset').addEventListener('click', () => {
        setTimeout(() => {
            form.querySelectorAll('.field').forEach((f) => f.classList.remove('invalid'));
            $('tanggal').value = $('tanggal').dataset.today;
            refresh();
        }, 0);
    });
});
</script>
<?php endif; ?>

<?php render_foot(); ?>
