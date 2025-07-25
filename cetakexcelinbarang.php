<?php
require "konek.php";
session_start();

// Set zona waktu Indonesia
date_default_timezone_set('Asia/Jakarta'); // WIB
// date_default_timezone_set('Asia/Makassar'); // WITA
// date_default_timezone_set('Asia/Jayapura'); // WIT

// Ambil parameter pencarian
$keyword = isset($_POST['search_term']) ? $_POST['search_term'] : (isset($_SESSION['saved_search']) ? $_SESSION['saved_search'] : '');

// Set header untuk file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Data_Barang_Masuk_".date('Y-m-d_H-i-s').".xls"); // Format nama file: 2023-10-15_14-30-45.xls
header("Pragma: no-cache");
header("Expires: 0");

// Query data dengan filter pencarian
$whereClause = '';
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

$query = mysqli_query($conn, "SELECT * FROM tambah" . $whereClause);
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
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="title">DATA BARANG MASUK</div>
    <div class="info">Tanggal Export: <?= date('d/m/Y H:i:s') ?> WIB</div>
    
    <table>
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
                <th>Harga</th>
                <th>Total Harga</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($query) > 0): ?>
                <?php $no = 1; ?>
                <?php while($data = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $data['nomor'] ?></td>
                        <td><?= $data['kdbarang'] ?></td>
                        <td><?= $data['nmbarang'] ?></td>
                        <td><?= $data['np'] ?></td>
                        <td><?= $data['tanggal'] ?></td>
                        <td><?= $data['satuan'] ?></td>
                        <td><?= $data['jumlah'] ?></td>
                        <td><?= number_format($data['harga'], 0, ',', '.') ?></td>
                        <td><?= number_format($data['totalharga'], 0, ',', '.') ?></td>
                        <td><?= $data['ket'] ?></td>
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