<!-- nambahbarang.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Penambahan Barang - Torsada</title>
    <link rel="stylesheet" href="output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <h1 class="hero">Form Penambahan Barang - Torsada</h1>
        <nav class="hamburger" id="klik">
            <div class="line"></div>
            <div class="line"></div>
            <div class="line"></div>
        </nav>
        <main class="link">
            <ul>
                <li><a href="masuk.php">Input Data Barang</a></li>
                <li><a href="output.php">Pengurangan Barang</a></li>
                <li><a href="informasimasukbarang.php">Riwayat Masuk Barang</a></li>
                <li><a href="riwayatkurangbarang.php">Riwayat Keluar Barang</a></li>
                <li><a href="inbarang.php">Riwayat Tambah Barang</a></li>
            </ul>
        </main>
    </header>

    <main>
        <div id="statusMessage"></div>

        <form action="formnambahbarang.php" method="post" class="form-container" id="formBarangMasuk">
            <?php
            require "konek.php";
            $query = mysqli_query($conn, "SELECT nomor, kdbarang, namabarang, satuan, harga, jumlah FROM input");
            $barang = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $barang[$row['kdbarang']] = $row;
            }
            mysqli_close($conn);
            ?>

            <div class="form-group">
                <label for="kdbarang">Kode Barang</label>
                <select name="kdbarang" id="kdbarang" class="form-control" required onchange="updateBarangInfo()">
                    <option value="">-- Pilih Kode Barang --</option>
                    <?php foreach ($barang as $kode => $data): ?>
                        <option value="<?= htmlspecialchars($kode) ?>">
                            <?= htmlspecialchars($kode) ?> - <?= htmlspecialchars($data['namabarang']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nomor">Nomor</label>
                <input type="number" name="nomor" id="nomor" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="nmbarang">Nama Barang</label>
                <input type="text" name="nmbarang" id="nmbarang" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="supplier">Supplier/Pemasok</label>
                <input type="text" name="supplier" id="supplier" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="satuan">Satuan</label>
                <input type="text" name="satuan" id="satuan" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="jumlah">Jumlah Tambahan</label>
                <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" required>
            </div>

            <div class="form-group">
                <label for="harga">Harga Satuan</label>
                <input type="number" name="harga" id="harga" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="totalharga">Total Harga</label>
                <input type="number" name="totalharga" id="totalharga" class="form-control" readonly>
            </div>

            <div class="form-group full-width">
                <label for="tanggal">Tanggal Masuk</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="ket">Keterangan</label>
                <textarea name="ket" id="ket" rows="3" class="form-control" required></textarea>
            </div>

            <div class="form-group full-width">
                <button type="submit" class="btn-submit"><i class="fas fa-plus-circle"></i> Tambah Stok</button>
            </div>
        </form>

        <script>
            const klik = document.getElementById('klik');
            klik.onclick = function () {
                document.querySelector('.link').classList.toggle('open');
            };

            const barangData = <?= json_encode($barang) ?>;

            function updateBarangInfo() {
                const kode = document.getElementById('kdbarang').value;
                const barang = barangData[kode];

                if (barang) {
                    const satuanNumerik = parseInt(barang.satuan) || 0;
                    const satuanText = barang.satuan.replace(satuanNumerik, '').trim() || 'set';

                    document.getElementById('nomor').value = barang.nomor;
                    document.getElementById('nmbarang').value = barang.namabarang;
                    document.getElementById('satuan').value = satuanNumerik + ' ' + satuanText;
                    document.getElementById('satuan').dataset.original = satuanNumerik;
                    document.getElementById('satuan').dataset.satuanText = satuanText;

                    document.getElementById('harga').value = barang.harga;
                    document.getElementById('totalharga').value = '';
                } else {
                    document.getElementById('nomor').value = '';
                    document.getElementById('nmbarang').value = '';
                    document.getElementById('satuan').value = '';
                    document.getElementById('harga').value = '';
                    document.getElementById('totalharga').value = '';
                }
            }

            function calculateTotalHarga() {
                const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
                const harga = parseInt(document.getElementById('harga').value) || 0;
                const total = jumlah * harga;
                document.getElementById('totalharga').value = total;

                const satuanInput = document.getElementById('satuan');
                const original = parseInt(satuanInput.dataset.original) || 0;
                const satuanText = satuanInput.dataset.satuanText || '';

                const newTotal = original + jumlah;
                satuanInput.value = newTotal + ' ' + satuanText;
            }

            document.getElementById('jumlah').addEventListener('input', calculateTotalHarga);

            function showStatusMessage(type, title, message, details = '') {
                const statusMessage = document.getElementById('statusMessage');
                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                statusMessage.innerHTML = `
                    <div class="status-message ${type}">
                        <i class="fas ${icon}"></i>
                        <div>
                            <h3>${title}</h3>
                            <p>${message}</p>
                            ${details ? `<p>${details}</p>` : ''}
                        </div>
                    </div>`;
                statusMessage.scrollIntoView({ behavior: 'smooth' });
                if (type === 'success') {
                    setTimeout(() => { statusMessage.innerHTML = ''; }, 5000);
                }
            }

            document.getElementById('formBarangMasuk').addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);

                const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
                const totalHarga = parseInt(document.getElementById('totalharga').value) || 0;

                if (jumlah <= 0) {
                    showStatusMessage('error', 'Error!', 'Jumlah harus lebih dari 0');
                    return;
                }

                formData.set('totalharga', totalHarga.toString());

                fetch('formnambahbarang.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        showStatusMessage('success', 'Sukses!', 'Stok berhasil ditambahkan',
                            `Kode: ${data.data.kode_barang} | Jumlah: ${data.data.ditambahkan} ${data.data.satuan}`);
                        document.getElementById('formBarangMasuk').reset();
                        updateBarangInfo();
                    } else {
                        showStatusMessage('error', 'Gagal!', data.message);
                    }
                })
                .catch(() => {
                    showStatusMessage('error', 'Network Error!', 'Gagal mengirim ke server.');
                });
            });

            document.addEventListener('DOMContentLoaded', function () {
                updateBarangInfo();
            });
        </script>
    </main>
</body>
</html>