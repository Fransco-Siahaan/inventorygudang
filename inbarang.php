<?php
require "konek.php";
session_start();

date_default_timezone_set('Asia/Jakarta');

// Check for saved search
$saved_search = isset($_SESSION['saved_search']) ? $_SESSION['saved_search'] : '';

// Use either the new search term or the saved one
$keyword = isset($_POST['cari']) ? $_POST['cari'] : $saved_search;
$whereClause = '';

// Jika ada keyword pencarian
if (!empty($keyword)) {
    $whereClause = " WHERE 
        nomor LIKE '%$keyword%' OR 
        kdbarang LIKE '%$keyword%' OR 
        nmbarang LIKE '%$keyword%' OR 
        np LIKE '%$keyword%' OR 
        tanggal LIKE '%$keyword%' OR 
        satuan LIKE '%$keyword%' OR 
        jumlah LIKE '%$keyword%' OR 
        harga LIKE '%$keyword%' OR 
        totalharga LIKE '%$keyword%' OR 
        ket LIKE '%$keyword%'";
}

$cekdata = "SELECT * FROM tambah" . $whereClause;
$query = mysqli_query($conn, $cekdata);
$alldata = mysqli_num_rows($query);
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

        .print button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .bx {
            font-size: 1.2em;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
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
    </style>
</head>

<body>

    <h1 class="hero">Informasi Data Tambah Barang</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="notification success">
            <?= $_SESSION['message'] ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="notification error">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="box">
            <ul class="link">
                <li><a href="masuk.php">Input Data Barang</a></li>
                <li><a href="nambahbarang.php">Tambah Barang</a></li>
                <li><a href="output.php">Pengurangan Barang</a></li>
                <li><a href="informasimasukbarang.php">Riwayat Masuk Barang</a></li>
                <li><a href="riwayatkurangbarang.php">Riwayat Pengurangan Barang</a></li>
            </ul>

            <div class="pencarian">
                <form action="" method="post" id="searchForm">
                    <input type="text" name="cari" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari data...">
                    <button type="submit" class="btn" style="background:none; border:none; cursor:pointer;" hidden>
                        <i class='bx bx-search'></i>
                    </button>
                </form>
            </div>

            <div class="print" style="display: flex; column-gap: 6px;">
                <form action="cetakpdfinbarang.php" method="post">
                    <input type="hidden" name="search_term" value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" style="background-color: #e74c3c;">
                        <i class='bx bx-printer'></i> Print PDF
                    </button>
                </form>
                <form action="cetakexcelinbarang.php" method="post">
                    <input type="hidden" name="search_term" value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" style="background-color: #27ae60;">
                        <i class='bx bx-table'></i> Export Excel
                    </button>
                </form>
                <form action="save.php" method="post" id="saveForm">
                    <input type="hidden" name="search_term" value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" style="background-color: #3498db;">
                        <i class='bx bx-save'></i> Save
                    </button>
                </form>
            </div>

            <div class="tabel">
                <main class="middle">
                    <table class="infromasi" border="1" cellspacing="0">
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
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($alldata > 0): ?>
                                <?php $x = 1; ?>
                                <?php while ($barang = mysqli_fetch_assoc($query)): ?>
                                    <tr>
                                        <td><?= $x ?></td>
                                        <td><?= htmlspecialchars($barang['nomor']) ?></td>
                                        <td><?= htmlspecialchars($barang['kdbarang']) ?></td>
                                        <td><?= htmlspecialchars($barang['nmbarang']) ?></td>
                                        <td><?= htmlspecialchars($barang['np']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($barang['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($barang['satuan']) ?></td>
                                        <td><?= number_format($barang['jumlah'], 0, ',', '.') ?></td>
                                        <td><?= number_format($barang['harga'], 0, ',', '.') ?></td>
                                        <td><?= number_format($barang['totalharga'], 0, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($barang['ket']) ?></td>
                                        <td>
                                            <form action="hapusdatainbarang.php" method="POST" class="delete-form">
                                                <input type="hidden" name="nomor" value="<?= htmlspecialchars($barang['nomor']) ?>">
                                                <input type="hidden" name="kdbarang" value="<?= htmlspecialchars($barang['kdbarang']) ?>">
                                                <button type="submit" class="delete-btn" onclick="return confirm('Yakin ingin menghapus data <?= htmlspecialchars($barang['nmbarang']) ?>?')">
                                                    <i class='bx bx-trash'></i> HAPUS
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php $x++; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" style="text-align:center;">Tidak ada data ditemukan</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </main>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Handle search form submission
                document.getElementById('searchForm').addEventListener('submit', function(e) {
                    // Clear saved search when doing a new search
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
            });
        </script>
    </div>
</body>
</html>