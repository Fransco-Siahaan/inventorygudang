<?php
require "konek.php";
session_start();

// Set timezone ke Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Ambil parameter pencarian
$keyword = isset($_POST['search_term']) ? $_POST['search_term'] : (isset($_SESSION['saved_search']) ? $_SESSION['saved_search'] : '');

// Set header untuk file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Data_Barang_Keluar_".date('Y-m-d_H-i-s').".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Query data dengan filter pencarian
$whereClause = '';
if (!empty($keyword)) {
    $whereClause = " WHERE 
        kdbarang LIKE '%$keyword%' OR 
        nmbarang LIKE '%$keyword%' OR 
        pengeluaran LIKE '%$keyword%' OR 
        tanggal LIKE '%$keyword%' OR 
        satuan LIKE '%$keyword%' OR 
        jumlah LIKE '%$keyword%' OR 
        harga LIKE '%$keyword%' OR 
        totalharga LIKE '%$keyword%' OR 
        ket LIKE '%$keyword%'";
}

$query = mysqli_query($conn, "SELECT * FROM keluar" . $whereClause);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Excel</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #00ccbb;
            font-weight: bold;
            color: #000;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .info {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="title">DATA BARANG KELUAR</div>
    <div class="info">
        Tanggal Export: <?= date('d/m/Y H:i:s') ?> WIB<br>
        <?php if (!empty($keyword)): ?>
        Filter Pencarian: "<?= htmlspecialchars($keyword) ?>"
        <?php endif; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Penerima</th>
                <th>Tanggal</th>
                <th>Satuan</th>
                <th>Jumlah</th>
                <th>Harga (Rp)</th>
                <th>Total (Rp)</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($query) > 0): ?>
                <?php $no = 1; ?>
                <?php while($data = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($data['nomor']) ?></td>
                        <td><?= htmlspecialchars($data['kdbarang']) ?></td>
                        <td><?= htmlspecialchars($data['nmbarang']) ?></td>
                        <td><?= htmlspecialchars($data['pengeluaran']) ?></td>
                        <td><?= date('d/m/Y', strtotime($data['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($data['satuan']) ?></td>
                        <td><?= number_format($data['jumlah'], 0, ',', '.') ?></td>
                        <td><?= number_format($data['harga'], 0, ',', '.') ?></td>
                        <td><?= number_format($data['totalharga'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($data['ket']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" style="text-align:center;">Tidak ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>