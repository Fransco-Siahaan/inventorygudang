<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Barang Masuk</title>
    <link rel="stylesheet" href="masuk1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <header>
        <h1 class="hero">Input Data Barang - Torsada</h1>
        <nav class="hamburger" id="klik">
            <div class="line"></div>
            <div class="line"></div>
            <div class="line"></div>
        </nav>
        <main class="link">
            <ul>
                <li><a href="output.php">Pengurangan Barang</a></li>
                <li><a href="nambahbarang.php">Penambahan Barang</a></li>
                <li><a href="informasimasukbarang.php">Riwayat Masuk Barang</a></li>
                <li><a href="riwayatkurangbarang.php">Riwayat Keluar Barang</a></li>
                <li><a href="inbarang.php">Riwayat Tambah Barang</a></li>
            </ul>
        </main>
    </header>

    <main>
        <!-- Tempat untuk menampilkan pesan status -->
        <div id="statusMessage"></div>

        <form action="forminputbarang.php" method="post" class="form-container" id="formBarangMasuk">
            <div class="form-group">
                <label for="kdbarang">Kode Barang</label>
                <input type="text" name="kdbarang" id="kdbarang" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="namabarang">Nama Barang</label>
                <input type="text" name="namabarang" id="namabarang" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="np">Nama Pengirim</label>
                <input type="text" name="np" id="np" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="satuan">Satuan</label>
                <select name="satuan" id="satuan" class="form-control" required>
                    <option value="">-- Pilih Satuan --</option>
                    <option value="set">Set</option>
                    <option value="pcs">Pcs</option>
                    <option value="unit">Unit</option>
                    <option value="buah">Buah</option>
                </select>
            </div>

            <div class="form-group">
                <label for="jumlah">Jumlah</label>
                <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" required>
            </div>

            <div class="form-group">
                <label for="harga">Harga</label>
                <input type="number" name="harga" id="harga" class="form-control" min="0" required>
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
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Kirim Data
                </button>
            </div>
        </form>
    </main>

    <script>
        const klik = document.getElementById('klik');
        klik.onclick = function() {
            document.querySelector('.link').classList.toggle('open');
        }
        // Fungsi untuk menghitung total harga
        function calculateTotalHarga() {
            const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
            const harga = parseInt(document.getElementById('harga').value) || 0;
            const totalharga = jumlah * harga;
            document.getElementById('totalharga').value = totalharga;
        }

        // Event listeners untuk perhitungan otomatis
        document.getElementById('jumlah').addEventListener('input', calculateTotalHarga);
        document.getElementById('harga').addEventListener('input', calculateTotalHarga);

        // Fungsi untuk menampilkan pesan status
        function showStatusMessage(type, title, message) {
            const statusMessage = document.getElementById('statusMessage');
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            statusMessage.innerHTML = `
                    <div class="status-message ${type}">
                        <i class="fas ${icon}"></i>
                        <div>
                            <h3>${title}</h3>
                            <p>${message}</p>
                        </div>
                    </div>
                `;

            statusMessage.scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Handle form submission dengan AJAX
        document.getElementById('formBarangMasuk').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('forminputbarang.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showStatusMessage('success', 'Berhasil!', data.message);
                        setTimeout(() => {
                            window.location.href = 'masuk.php';
                        }, 2000);
                    } else {
                        showStatusMessage('error', 'Error!', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showStatusMessage('error', 'Error!', 'Terjadi kesalahan saat memproses data');
                });
        });
    </script>
</body>

</html>