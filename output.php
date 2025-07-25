<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Barang Keluar</title>
    <link rel="stylesheet" href="output.css">
    <!-- Tambahkan Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <header>
        <h1 class="hero">Data Pengeluaran Barang - Torsada</h1>
        <nav class="hamburger" id="klik">
            <div class="line"></div>
            <div class="line"></div>
            <div class="line"></div>
        </nav>
        <main class="link">
            <ul>
                <li><a href="masuk.php">Input Data Barang</a></li>
                <li><a href="nambahbarang.php">Penambahan Barang</a></li>
                <li><a href="informasimasukbarang.php">Riwayat Masuk Barang</a></li>
                <li><a href="inbarang.php">Riwayat Tambah Barang</a></li>
                <li><a href="riwayatkurangbarang.php">Riwayat Keluar Barang</a></li>
            </ul>
        </main>
    </header>

    <main>
        <!-- Tempat untuk menampilkan pesan status -->
        <div id="statusMessage"></div>

        <form action="formoutputbarang.php" method="post" class="form-container" id="formBarangKeluar">
            <?php
            require "konek.php";

            $query = mysqli_query($conn, "SELECT nomor, kdbarang, namabarang, satuan, harga FROM input");
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
                <label for="pengeluaran">Keluar Ke</label>
                <input type="text" name="pengeluaran" id="pengeluaran" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="satuan">Satuan</label>
                <input type="text" name="satuan" id="satuan" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="jumlah">Jumlah</label>
                <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" required>
            </div>

            <div class="form-group">
                <label for="harga">Harga</label>
                <input type="number" name="harga" id="harga" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="totalharga">Total Harga</label>
                <input type="number" name="totalharga" id="totalharga" class="form-control" readonly>
            </div>

            <div class="form-group full-width">
                <label for="tanggal">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="ket">Keterangan</label>
                <textarea name="ket" id="ket" rows="3" class="form-control" required></textarea>
            </div>

            <div class="form-group full-width">
                <button type="submit" class="btn-submit">Kirim</button>
            </div>
        </form>

        <script>
            const klik = document.getElementById('klik');
            klik.onclick = function() {
                document.querySelector('.link').classList.toggle('open');
            }

            const barangData = <?= json_encode($barang) ?>;

            function updateBarangInfo() {
                const kodeBarang = document.getElementById('kdbarang').value;
                if (kodeBarang && barangData[kodeBarang]) {
                    const barang = barangData[kodeBarang];
                    document.getElementById('nomor').value = barang.nomor || '';
                    document.getElementById('nmbarang').value = barang.namabarang;

                    // Simpan data satuan asli untuk perhitungan
                    const satuanAsli = barang.satuan;
                    const satuanNumerik = parseInt(satuanAsli) || 0;
                    const satuanText = satuanAsli.replace(satuanNumerik, '').trim() || 'set';

                    // Simpan data awal di atribut data-*
                    document.getElementById('satuan').dataset.original = satuanNumerik;
                    document.getElementById('satuan').dataset.satuanText = satuanText;
                    document.getElementById('satuan').dataset.originalTotalHarga = barang.totalharga || 0;

                    // Tampilkan satuan asli
                    document.getElementById('satuan').value = satuanNumerik + ' ' + satuanText;

                    document.getElementById('harga').value = barang.harga;
                    document.getElementById('totalharga').value = '';
                    document.getElementById('sisa_totalharga').value = barang.totalharga || 0;
                } else {
                    document.getElementById('nomor').value = '';
                    document.getElementById('nmbarang').value = '';
                    document.getElementById('satuan').value = '';
                    document.getElementById('satuan').removeAttribute('data-original');
                    document.getElementById('satuan').removeAttribute('data-satuan-text');
                    document.getElementById('harga').value = '';
                    document.getElementById('totalharga').value = '';
                }
            }

            function calculateTotalHarga() {
                const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
                const harga = parseInt(document.getElementById('harga').value) || 0;
                const totalharga = jumlah * harga;
                document.getElementById('totalharga').value = totalharga;

                // Hitung sisa stok dan total harga
                const satuanEl = document.getElementById('satuan');
                const original = parseInt(satuanEl.dataset.original) || 0;
                const satuanText = satuanEl.dataset.satuanText || 'set';
                const originalTotalHarga = parseFloat(satuanEl.dataset.originalTotalHarga) || 0;

                if (original && jumlah) {
                    const sisa = original - jumlah;
                    satuanEl.value = sisa + ' ' + satuanText;

                    // Hitung sisa total harga
                    const sisaTotalHarga = originalTotalHarga - totalharga;
                    document.getElementById('sisa_totalharga').value = sisaTotalHarga > 0 ? sisaTotalHarga : 0;
                }
            }

            // Event listener untuk perubahan jumlah
            document.getElementById('jumlah').addEventListener('input', calculateTotalHarga);

            // Fungsi untuk menampilkan pesan status
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
            </div>
        `;

                statusMessage.scrollIntoView({
                    behavior: 'smooth'
                });
            }

            document.getElementById('formBarangKeluar').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
                const nomor = document.getElementById('nomor').value;

                if (jumlah <= 0) {
                    showStatusMessage('error', 'Error!', 'Jumlah harus lebih dari 0');
                    return;
                }

                // Tambahkan nomor ke FormData
                formData.append('nomor', nomor);

                fetch('formoutputbarang.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            showStatusMessage(
                                'success',
                                'Berhasil!',
                                data.message,
                                `Sisa stok: ${data.sisa_stok}${data.satuan}`
                            );
                            this.reset();
                            updateBarangInfo();
                        } else {
                            showStatusMessage('error', 'Error!', data.message);
                        }
                    })
                    .catch(error => {
                        showStatusMessage('error', 'Error!', 'Terjadi kesalahan: ' + error.message);
                    });
            });

            // Inisialisasi awal
            document.addEventListener('DOMContentLoaded', function() {
                updateBarangInfo();
            });
        </script>
    </main>
</body>

</html>