<?php
require "konek.php";
session_start();

// Set timezone ke Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk membersihkan input
function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check for saved search
$saved_search = isset($_SESSION['saved_search']) ? clean_input($_SESSION['saved_search']) : '';
$keyword = isset($_POST['cari']) ? clean_input($_POST['cari']) : $saved_search;
$whereClause = '';
$params = [];
$types = '';

// Jika ada keyword pencarian
if (!empty($keyword)) {
    $whereClause = " WHERE 
        nomor LIKE CONCAT('%', ?, '%') OR 
        kdbarang LIKE CONCAT('%', ?, '%') OR 
        namabarang LIKE CONCAT('%', ?, '%') OR 
        np LIKE CONCAT('%', ?, '%') OR 
        tanggal LIKE CONCAT('%', ?, '%') OR 
        satuan LIKE CONCAT('%', ?, '%') OR 
        jumlah LIKE CONCAT('%', ?, '%') OR 
        harga LIKE CONCAT('%', ?, '%') OR 
        totalharga LIKE CONCAT('%', ?, '%') OR 
        ket LIKE CONCAT('%', ?, '%')";

    // Tambahkan parameter untuk setiap kolom yang dicari
    for ($i = 0; $i < 10; $i++) {
        $params[] = $keyword;
    }
    $types = str_repeat('s', count($params));
}

// Pagination
$rows_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $rows_per_page;

// Query data dengan pagination
$cekdata = "SELECT * FROM input" . $whereClause . " LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $cekdata);

if ($whereClause !== '') {
    $params[] = $rows_per_page;
    $params[] = $offset;
    $types .= 'ii';
    mysqli_stmt_bind_param($stmt, $types, ...$params);
} else {
    mysqli_stmt_bind_param($stmt, 'ii', $rows_per_page, $offset);
}

mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);

// Query untuk total data (untuk pagination)
$count_query = "SELECT COUNT(*) as total FROM input" . $whereClause;
$count_stmt = mysqli_prepare($conn, $count_query);

if ($whereClause !== '') {
    $count_types = str_repeat('s', 10); // hanya untuk 10 kolom pencarian
    mysqli_stmt_bind_param($count_stmt, $count_types, ...array_slice($params, 0, 10));
}

mysqli_stmt_execute($count_stmt);
$total_result = mysqli_stmt_get_result($count_stmt);
$total_row = mysqli_fetch_assoc($total_result);
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $rows_per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabel Data Tambah Barang</title>
    <link rel="stylesheet" href="imbarang.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: white;
        }

        .success {
            background-color: #2ecc71;
        }

        .error {
            background-color: #e74c3c;
        }

        .print-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .print-buttons button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .print-buttons button i {
            font-size: 1.1em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #00ccbb;
            font-weight: bold;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-top: 20px;
        }

        .info-btn {
            background-color: #005eff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .info-btn:hover {
            background-color: #005eff;
            opacity: 0.8;
        }

        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            width: 80%;
            max-width: 900px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid #ccc;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            border-radius: 8px;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .close-btn {
            float: right;
            cursor: pointer;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .popup-tabs {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .popup-tabs button {
            padding: 8px 16px;
            background-color: #f0f0f0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .popup-tabs button.active {
            background-color: #00ccbb;
            color: white;
        }

        .popup-tabs form {
            margin-left: auto;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .detail-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .detail-table th {
            width: 30%;
            background-color: #f5f5f5;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
        }

        .pagination a:hover {
            background-color: #f0f0f0;
        }

        .pagination .active {
            background-color: #00ccbb;
            color: white;
            border-color: #00ccbb;
        }

        .search-btn {
            padding: 8px 15px;
            background-color: #00ccbb;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .search-btn:hover {
            background-color: #00aa99;
        }

        /* CSS untuk tabel gabungan */
        .jenis-masuk {
            background-color: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }

        .jenis-keluar {
            background-color: #f8d7da;
            color: #721c24;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }

        .tab-content h4 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #00ccbb;
            padding-bottom: 5px;
        }
    </style>
</head>

<body>
    <!-- Overlay dan Popup -->
    <div class="overlay" id="overlay" onclick="closePopup()"></div>

    <div class="popup" id="popup">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <h2>Detail Barang</h2>

        <table class="detail-table">
            <tr>
                <th>Nomor</th>
                <td id="popup-nomor"></td>
            </tr>
            <tr>
                <th>Kode Barang</th>
                <td id="popup-kdbarang"></td>
            </tr>
            <tr>
                <th>Nama Barang</th>
                <td id="popup-namabarang"></td>
            </tr>
            <tr>
                <th>Nama Pengirim</th>
                <td id="popup-np"></td>
            </tr>
            <tr>
                <th>Tanggal</th>
                <td id="popup-tanggal"></td>
            </tr>
            <tr>
                <th>Satuan</th>
                <td id="popup-satuan"></td>
            </tr>
            <tr>
                <th>Jumlah</th>
                <td id="popup-jumlah"></td>
            </tr>
            <tr>
                <th>Harga (Rp)</th>
                <td id="popup-harga"></td>
            </tr>
            <tr>
                <th>Total Harga (Rp)</th>
                <td id="popup-totalharga"></td>
            </tr>
            <tr>
                <th>Keterangan</th>
                <td id="popup-ket"></td>
            </tr>
        </table>

        <h3>Riwayat Barang</h3>

        <!-- Tabel Gabungan (Default) -->
        <div id="tab-gabungan" class="tab-content active">
            <h4>Semua Transaksi (Masuk & Keluar)</h4>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jenis</th>
                        <th>Nomor</th>
                        <th>Kode Barang</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody id="gabungan-body">
                    <!-- Data akan diisi oleh JavaScript -->
                </tbody>
            </table>
        </div>

        <div class="popup-tabs">
            <button onclick="showTab('gabungan')" class="active">Semua Transaksi</button>
            <button onclick="showTab('tambah')">Barang Tambah</button>
            <button onclick="showTab('keluar')">Barang Keluar</button>
            <form action="cetakexcelsemuabarang.php" method="post" id="exportExcelForm">
                <input type="hidden" name="search_term" value="<?= htmlspecialchars($keyword) ?>">
                <input type="hidden" name="kdbarang_spesifik" id="kdbarang_spesifik" value="">
                <button type="submit" style="background-color: #27ae60;">
                    <i class='bx bx-table'></i> Export Excel
                </button>
            </form>
        </div>

        <div id="tab-tambah" class="tab-content">
            <h4>Riwayat Barang Masuk</h4>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor</th>
                        <th>Kode Barang</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody id="tambah-body">
                    <!-- Data akan diisi oleh JavaScript -->
                </tbody>
            </table>
        </div>

        <div id="tab-keluar" class="tab-content">
            <h4>Riwayat Barang Keluar</h4>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor</th>
                        <th>Kode Barang</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody id="keluar-body">
                    <!-- Data akan diisi oleh JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="container">
        <h1 class="hero">Informasi Data Barang Masuk</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="notification success">
                <?= htmlspecialchars($_SESSION['message']) ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="notification error">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="box">
            <ul class="link">
                <li><a href="masuk.php">Input Data Barang</a></li>
                <li><a href="nambahbarang.php">Tambah Barang</a></li>
                <li><a href="output.php">Pengurangan Barang</a></li>
                <li><a href="riwayatkurangbarang.php">Riwayat Pengurangan Barang</a></li>
                <li><a href="inbarang.php">Riwayat Penambahan Barang</a></li>
            </ul>

            <div class="pencarian">
                <form action="" method="post" id="searchForm">
                    <input type="text" name="cari" value="<?= htmlspecialchars($keyword) ?>"
                        placeholder="Cari data..." aria-label="Cari data">
                </form>
            </div>

            <div class="print-buttons">
                <form action="cetakpdf.php" method="post">
                    <input type="hidden" name="search_term" value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" style="background-color: #e74c3c;">
                        <i class='bx bx-printer'></i> Print PDF
                    </button>
                </form>
                <form action="cetakexcel.php" method="post">
                    <input type="hidden" name="search_term" value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" style="background-color: #27ae60;">
                        <i class='bx bx-table'></i> Export Excel
                    </button>
                </form>
                <form action="save.php" method="post" id="saveForm">
                    <input type="hidden" name="search_term" value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" style="background-color: #3498db;">
                        <i class='bx bx-save'></i> Simpan Pencarian
                    </button>
                </form>
            </div>

            <div class="tabel">
                <main class="middle">
                    <?php if ($total_data > 0): ?>
                        <table class="infromasi">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor</th>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Nama Pengirim</th>
                                    <th>Tanggal</th>
                                    <th>Satuan</th>
                                    <th>Jumlah</th>
                                    <th>Harga (Rp)</th>
                                    <th>Total Harga (Rp)</th>
                                    <th>Keterangan</th>
                                    <th colspan="2" style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = ($current_page - 1) * $rows_per_page + 1; ?>
                                <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($row['nomor']) ?></td>
                                        <td><?= htmlspecialchars($row['kdbarang']) ?></td>
                                        <td><?= htmlspecialchars($row['namabarang']) ?></td>
                                        <td><?= htmlspecialchars($row['np']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($row['satuan']) ?></td>
                                        <td><?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                        <td><?= number_format($row['harga'], 0, ',', '.') ?></td>
                                        <td><?= number_format($row['totalharga'], 0, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($row['ket']) ?></td>
                                        <td>
                                            <form action="hapusdata.php" method="POST" class="delete-form">
                                                <input type="hidden" name="nomor" value="<?= htmlspecialchars($row['nomor']) ?>">
                                                <button type="submit" class="delete-btn"
                                                    onclick="return confirm('Yakin ingin menghapus data <?= htmlspecialchars(addslashes($row['namabarang'])) ?>?')">
                                                    <i class='bx bx-trash'></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <button onclick="openPopup(
                                                '<?= htmlspecialchars($row['nomor']) ?>',
                                                '<?= htmlspecialchars($row['kdbarang']) ?>',
                                                '<?= htmlspecialchars($row['namabarang']) ?>',
                                                '<?= htmlspecialchars($row['np']) ?>',
                                                '<?= date('d/m/Y', strtotime($row['tanggal'])) ?>',
                                                '<?= htmlspecialchars($row['satuan']) ?>',
                                                '<?= number_format($row['jumlah'], 0, ',', '.') ?>',
                                                '<?= number_format($row['harga'], 0, ',', '.') ?>',
                                                '<?= number_format($row['totalharga'], 0, ',', '.') ?>',
                                                '<?= htmlspecialchars($row['ket']) ?>'
                                            )" class="info-btn">
                                                <i class='bx bx-info-circle'></i> INFO
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=1&cari=<?= urlencode($keyword) ?>">First</a>
                                <a href="?page=<?= $current_page - 1 ?>&cari=<?= urlencode($keyword) ?>">Prev</a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?page=<?= $i ?>&cari=<?= urlencode($keyword) ?>" <?= ($i == $current_page) ? 'class="active"' : '' ?>>
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?= $current_page + 1 ?>&cari=<?= urlencode($keyword) ?>">Next</a>
                                <a href="?page=<?= $total_pages ?>&cari=<?= urlencode($keyword) ?>">Last</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">Tidak ada data ditemukan</div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka popup dengan data spesifik
        function openPopup(nomor, kdbarang, namabarang, np, tanggal, satuan, jumlah, harga, totalharga, ket) {
            // Isi data utama
            document.getElementById('popup-nomor').textContent = nomor;
            document.getElementById('popup-kdbarang').textContent = kdbarang;
            document.getElementById('popup-namabarang').textContent = namabarang;
            document.getElementById('popup-np').textContent = np;
            document.getElementById('popup-tanggal').textContent = tanggal;
            document.getElementById('popup-satuan').textContent = satuan;
            document.getElementById('popup-jumlah').textContent = jumlah;
            document.getElementById('popup-harga').textContent = harga;
            document.getElementById('popup-totalharga').textContent = totalharga;
            document.getElementById('popup-ket').textContent = ket;

            // Set kode barang untuk export Excel spesifik
            document.getElementById('kdbarang_spesifik').value = kdbarang;

            // Ambil data riwayat tambah dan keluar berdasarkan kode barang
            fetchRiwayatBarang(kdbarang);

            // Tampilkan popup dan set tab default ke gabungan
            showTab('gabungan');
            document.getElementById('popup').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        // Fungsi untuk mengambil data riwayat barang
        function fetchRiwayatBarang(kdbarang) {
            let dataTambah = [];
            let dataKeluar = [];

            // Ambil data tambah
            fetch(`get_riwayat.php?kdbarang=${encodeURIComponent(kdbarang)}&type=tambah`)
                .then(response => response.json())
                .then(data => {
                    dataTambah = data;
                    populateTambahTable(data);
                    // Setelah data tambah didapat, ambil data keluar
                    return fetch(`get_riwayat.php?kdbarang=${encodeURIComponent(kdbarang)}&type=keluar`);
                })
                .then(response => response.json())
                .then(data => {
                    dataKeluar = data;
                    populateKeluarTable(data);
                    // Gabungkan data dan tampilkan di tabel gabungan
                    populateGabunganTable(dataTambah, dataKeluar);
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    document.getElementById('tambah-body').innerHTML = '<tr><td colspan="6" style="text-align:center;">Error loading data</td></tr>';
                    document.getElementById('keluar-body').innerHTML = '<tr><td colspan="6" style="text-align:center;">Error loading data</td></tr>';
                    document.getElementById('gabungan-body').innerHTML = '<tr><td colspan="7" style="text-align:center;">Error loading data</td></tr>';
                });
        }

        // Fungsi untuk mengisi tabel tambah
        function populateTambahTable(data) {
            const tbody = document.getElementById('tambah-body');
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Tidak ada data barang masuk</td></tr>';
            } else {
                data.forEach((row, index) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${row.nomor || ''}</td>
                        <td>${row.kdbarang || ''}</td>
                        <td>${row.jumlah || ''}</td>
                        <td>${row.tanggal || ''}</td>
                        <td>${row.ket || ''}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }

        // Fungsi untuk mengisi tabel keluar
        function populateKeluarTable(data) {
            const tbody = document.getElementById('keluar-body');
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Tidak ada data barang keluar</td></tr>';
            } else {
                data.forEach((row, index) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${row.nomor || ''}</td>
                        <td>${row.kdbarang || ''}</td>
                        <td>${row.jumlah || ''}</td>
                        <td>${row.tanggal || ''}</td>
                        <td>${row.ket || ''}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }

        // Fungsi untuk mengisi tabel gabungan
        function populateGabunganTable(dataTambah, dataKeluar) {
            const tbody = document.getElementById('gabungan-body');
            tbody.innerHTML = '';

            // Gabungkan data dengan menambahkan field 'jenis'
            let gabunganData = [];

            dataTambah.forEach(row => {
                gabunganData.push({
                    ...row,
                    jenis: 'MASUK',
                    tanggal_sort: new Date(row.tanggal)
                });
            });

            dataKeluar.forEach(row => {
                gabunganData.push({
                    ...row,
                    jenis: 'KELUAR',
                    tanggal_sort: new Date(row.tanggal)
                });
            });

            // Urutkan berdasarkan tanggal (terbaru di atas)
            gabunganData.sort((a, b) => b.tanggal_sort - a.tanggal_sort);

            if (gabunganData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Tidak ada data transaksi</td></tr>';
            } else {
                gabunganData.forEach((row, index) => {
                    const tr = document.createElement('tr');
                    const jenisClass = row.jenis === 'MASUK' ? 'jenis-masuk' : 'jenis-keluar';
                    tr.innerHTML = `
                        <td>${index + 1}</td>
                        <td><span class="${jenisClass}">${row.jenis}</span></td>
                        <td>${row.nomor || ''}</td>
                        <td>${row.kdbarang || ''}</td>
                        <td>${row.jumlah || ''}</td>
                        <td>${row.tanggal || ''}</td>
                        <td>${row.ket || ''}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }

        function closePopup() {
            document.getElementById('popup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
            // Reset form
            document.getElementById('kdbarang_spesifik').value = '';
        }

        function showTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.popup-tabs button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`tab-${tab}`).classList.add('active');
        }

        // Handle search form submission
        document.getElementById('searchForm').addEventListener('submit', function() {
            fetch('save.php', {
                method: 'POST',
                body: new FormData(document.getElementById('saveForm'))
            });
        });

        // Handle save form submission
        document.getElementById('saveForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('save.php', {
                method: 'POST',
                body: new FormData(this)
            }).then(response => {
                alert('Pencarian berhasil disimpan!');
            });
        });
    </script>
</body>

</html>