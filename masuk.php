<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

render_head('Input Barang Baru', 'masuk', 'Daftarkan kode barang yang belum ada di gudang');
?>

<div class="grid-2">
    <section class="card" aria-labelledby="h-form">
        <div class="card-head">
            <h2 id="h-form">Data Barang</h2>
            <span class="sub">Kolom bertanda <span style="color:var(--red)">*</span> wajib diisi</span>
        </div>

        <form id="formMasuk" class="card-body" action="forminputbarang.php" method="post" novalidate>
            <div class="form-grid">
                <div class="field" data-field="kdbarang">
                    <label for="kdbarang" class="req">Kode Barang</label>
                    <input class="input" type="text" name="kdbarang" id="kdbarang" maxlength="50"
                           placeholder="Contoh: BRG-0001" autocomplete="off" required>
                    <span class="err">Kode barang wajib diisi.</span>
                </div>

                <div class="field" data-field="namabarang">
                    <label for="namabarang" class="req">Nama Barang</label>
                    <input class="input" type="text" name="namabarang" id="namabarang"
                           placeholder="Contoh: Kabel LAN Cat6" required>
                    <span class="err">Nama barang wajib diisi.</span>
                </div>

                <div class="field" data-field="np">
                    <label for="np" class="req">Nama Pengirim / Supplier</label>
                    <input class="input" type="text" name="np" id="np" placeholder="Nama pengirim" required>
                    <span class="err">Nama pengirim wajib diisi.</span>
                </div>

                <div class="field" data-field="satuan">
                    <label for="satuan" class="req">Satuan</label>
                    <select class="select" name="satuan" id="satuan" required>
                        <option value="">Pilih satuan…</option>
                        <option value="set">Set</option>
                        <option value="pcs">Pcs</option>
                        <option value="unit">Unit</option>
                        <option value="buah">Buah</option>
                    </select>
                    <span class="err">Pilih satuan.</span>
                </div>

                <div class="field" data-field="jumlah">
                    <label for="jumlah" class="req">Jumlah Awal</label>
                    <input class="input" type="number" name="jumlah" id="jumlah" min="1" step="1" inputmode="numeric" placeholder="0" required>
                    <span class="err">Jumlah minimal 1.</span>
                </div>

                <div class="field" data-field="harga">
                    <label for="harga" class="req">Harga Satuan (Rp)</label>
                    <div class="input-money">
                        <span>Rp</span>
                        <input class="input" type="number" name="harga" id="harga" min="0" step="any" inputmode="decimal" placeholder="0" required>
                    </div>
                    <span class="err">Harga tidak boleh negatif.</span>
                </div>

                <div class="field" data-field="tanggal">
                    <label for="tanggal" class="req">Tanggal Masuk</label>
                    <input class="input" type="date" name="tanggal" id="tanggal" value="<?= e(date('Y-m-d')) ?>" data-today="<?= e(date('Y-m-d')) ?>" required>
                    <span class="err">Tanggal wajib diisi.</span>
                </div>

                <div class="field" data-field="ket">
                    <label for="ket" class="req">Keterangan</label>
                    <input class="input" type="text" name="ket" id="ket" placeholder="Contoh: stok awal, PO #123" required>
                    <span class="err">Keterangan wajib diisi.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="reset" class="btn btn-outline" id="btnReset">
                    <i class="fa-solid fa-rotate-left"></i> Kosongkan
                </button>
                <button type="submit" class="btn btn-green" id="btnSubmit">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Barang
                </button>
            </div>
        </form>
    </section>

    <aside class="summary" aria-label="Ringkasan">
        <section class="summary-box" aria-live="polite">
            <div class="summary-row"><span>Kode</span><b id="sumKode">–</b></div>
            <div class="summary-row"><span>Nama</span><b id="sumNama" style="font-size:14px;max-width:60%;text-align:right;overflow-wrap:anywhere">–</b></div>
            <div class="summary-row"><span>Stok awal</span><b id="sumStok">0</b></div>
            <div class="summary-row"><span>Harga satuan</span><b id="sumHarga">Rp 0</b></div>
            <div class="summary-row total"><span>Total nilai</span><b id="sumTotal">Rp 0</b></div>
        </section>

        <div class="card card-body" style="padding:18px">
            <h2 style="font-size:15px;margin-bottom:10px"><i class="fa-regular fa-lightbulb" style="color:var(--brand-600)"></i> Tips</h2>
            <ul class="tips">
                <li>Kode barang harus unik. Jika sudah ada, gunakan menu <b>Tambah Stok</b>.</li>
                <li>Total nilai dihitung otomatis dari jumlah × harga satuan.</li>
                <li>Setelah berhasil disimpan, barang langsung muncul di <b>Daftar Stok</b>.</li>
            </ul>
        </div>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formMasuk');
    const btnSubmit = document.getElementById('btnSubmit');
    const $ = (id) => document.getElementById(id);

    // Ringkasan live
    function refresh() {
        const jumlah = parseInt($('jumlah').value, 10) || 0;
        const harga = parseFloat($('harga').value) || 0;
        const satuan = $('satuan').value;
        $('sumKode').textContent = $('kdbarang').value.trim() || '–';
        $('sumNama').textContent = $('namabarang').value.trim() || '–';
        $('sumStok').textContent = jumlah ? `${idn(jumlah)} ${satuan}`.trim() : '0';
        $('sumHarga').textContent = rp(harga);
        $('sumTotal').textContent = rp(jumlah * harga);
    }
    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();

    // Validasi per kolom: tampilkan pesan di bawah field yang bermasalah
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
            const res = await fetch('forminputbarang.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json();
            if (data.status === 'success') {
                // Halaman dimuat ulang; notifikasi sukses tampil dari session
                window.location.href = 'masuk.php';
                return;
            }
            toast('error', data.message || 'Gagal menyimpan data.');
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

<?php render_foot(); ?>
