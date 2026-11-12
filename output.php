<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

$barang = db_fetch_all(
    $conn,
    'SELECT nomor, kdbarang, namabarang, jumlah, satuan, harga FROM input ORDER BY kdbarang ASC'
);

render_head('Pengeluaran Barang', 'output', 'Catat barang yang keluar dari gudang');
?>

<?php if (!$barang): ?>
    <section class="card">
        <div class="empty">
            <i class="fa-solid fa-box-open" aria-hidden="true"></i>
            <b>Belum ada barang untuk dikeluarkan</b>
            Daftarkan barang terlebih dahulu di menu <a href="masuk.php">Input Barang Baru</a>.
        </div>
    </section>
<?php else: ?>
<div class="grid-2">
    <section class="card" aria-labelledby="h-form">
        <div class="card-head">
            <h2 id="h-form">Form Pengeluaran</h2>
            <span class="sub">Stok akan berkurang otomatis setelah disimpan</span>
        </div>

        <form id="formKeluar" class="card-body" action="formoutputbarang.php" method="post" novalidate>
            <input type="hidden" name="nomor" id="nomor">

            <div class="form-grid">
                <div class="field full" data-field="kdbarang">
                    <label for="kdbarang" class="req">Pilih Barang</label>
                    <select class="select" name="kdbarang" id="kdbarang" required>
                        <option value="">Pilih kode barang…</option>
                        <?php foreach ($barang as $b): ?>
                            <option value="<?= e($b['kdbarang']) ?>"
                                    data-nomor="<?= e($b['nomor']) ?>"
                                    data-nama="<?= e($b['namabarang']) ?>"
                                    data-stok="<?= e($b['jumlah']) ?>"
                                    data-satuan="<?= e(split_satuan($b['satuan'])[1]) ?>"
                                    data-harga="<?= e($b['harga']) ?>"
                                    <?= (int) $b['jumlah'] <= 0 ? 'disabled' : '' ?>>
                                <?= e($b['kdbarang']) ?> — <?= e($b['namabarang']) ?> (stok <?= angka($b['jumlah']) ?><?= (int) $b['jumlah'] <= 0 ? ', habis' : '' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="err">Pilih barang terlebih dahulu.</span>
                </div>

                <div class="field" data-field="nmbarang">
                    <label for="nmbarang">Nama Barang</label>
                    <input class="input" type="text" name="nmbarang" id="nmbarang" readonly tabindex="-1" placeholder="Otomatis">
                </div>

                <div class="field" data-field="pengeluaran">
                    <label for="pengeluaran" class="req">Keluar Ke</label>
                    <input class="input" type="text" name="pengeluaran" id="pengeluaran" required placeholder="Nama penerima / tujuan">
                    <span class="err">Kolom ini wajib diisi.</span>
                </div>

                <div class="field" data-field="jumlah">
                    <label for="jumlah" class="req">Jumlah Keluar</label>
                    <input class="input" type="number" name="jumlah" id="jumlah" min="1" step="1" inputmode="numeric" required placeholder="0">
                    <span class="err" id="errJumlah">Jumlah minimal 1.</span>
                </div>

                <div class="field" data-field="harga">
                    <label for="harga">Harga Satuan</label>
                    <div class="input-money">
                        <span>Rp</span>
                        <input class="input" type="text" id="harga" readonly tabindex="-1" placeholder="0">
                    </div>
                </div>

                <div class="field" data-field="tanggal">
                    <label for="tanggal" class="req">Tanggal</label>
                    <input class="input" type="date" name="tanggal" id="tanggal" value="<?= e(date('Y-m-d')) ?>" data-today="<?= e(date('Y-m-d')) ?>" required>
                    <span class="err">Tanggal wajib diisi.</span>
                </div>

                <div class="field full" data-field="ket">
                    <label for="ket" class="req">Keterangan</label>
                    <textarea class="textarea" name="ket" id="ket" rows="3" required placeholder="Contoh: pengiriman ke proyek A"></textarea>
                    <span class="err">Keterangan wajib diisi.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="reset" class="btn btn-outline" id="btnReset"><i class="fa-solid fa-rotate-left"></i> Kosongkan</button>
                <button type="submit" class="btn" id="btnSubmit"><i class="fa-solid fa-paper-plane"></i> Simpan Pengeluaran</button>
            </div>
        </form>
    </section>

    <aside class="summary" aria-label="Ringkasan">
        <section class="summary-box" aria-live="polite">
            <div class="summary-row"><span>Stok tersedia</span><b id="sumTersedia">–</b></div>
            <div class="summary-row"><span>Akan dikeluarkan</span><b id="sumKeluar">–</b></div>
            <div class="summary-row total"><span>Sisa stok</span><b id="sumSisa">–</b></div>
            <div class="summary-row"><span>Nilai pengeluaran</span><b id="sumNilai">Rp 0</b></div>
        </section>
        <div class="notice info" id="infoPilih">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>Pilih barang untuk melihat stok yang tersedia.</span>
        </div>
        <div class="notice danger hidden" id="infoKurang" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>Jumlah melebihi stok yang tersedia.</span>
        </div>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formKeluar');
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
            $('nomor').value = '';
            $('nmbarang').value = '';
            $('harga').value = '';
            ['sumTersedia', 'sumKeluar', 'sumSisa'].forEach((id) => $(id).textContent = '–');
            $('sumNilai').textContent = rp(0);
            $('infoPilih').classList.remove('hidden');
            $('infoKurang').classList.add('hidden');
            return;
        }
        const stok = parseInt(d.stok, 10) || 0;
        const harga = parseFloat(d.harga) || 0;
        const satuan = d.satuan || '';
        const kurang = jumlah > stok;

        $('nomor').value = d.nomor;
        $('nmbarang').value = d.nama;
        $('harga').value = idn(harga);
        $('sumTersedia').textContent = `${idn(stok)} ${satuan}`.trim();
        $('sumKeluar').textContent = jumlah ? `− ${idn(jumlah)} ${satuan}`.trim() : '–';
        $('sumSisa').textContent = jumlah ? `${idn(Math.max(0, stok - jumlah))} ${satuan}`.trim() : `${idn(stok)} ${satuan}`.trim();
        $('sumNilai').textContent = rp(jumlah * harga);
        $('infoPilih').classList.add('hidden');
        $('infoKurang').classList.toggle('hidden', !kurang);
        $('jumlah').max = stok;
        $('errJumlah').textContent = kurang ? `Stok hanya ${idn(stok)} ${satuan}.` : 'Jumlah minimal 1.';
        $('jumlah').closest('.field').classList.toggle('invalid', kurang);
        btnSubmit.disabled = kurang;
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
        const d = selected();
        if (d && (parseInt($('jumlah').value, 10) || 0) > (parseInt(d.stok, 10) || 0)) { ok = false; }
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
            toast('warning', 'Periksa kembali kolom yang bertanda merah.');
            return;
        }
        setLoading(btnSubmit, true);
        try {
            const res = await fetch('formoutputbarang.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.href = 'output.php';
                return;
            }
            toast('error', data.message || 'Gagal menyimpan pengeluaran.');
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
