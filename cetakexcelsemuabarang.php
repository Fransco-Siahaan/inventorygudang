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

// Ambil parameter pencarian dan kode barang spesifik
$keyword = isset($_POST['search_term']) ? clean_input($_POST['search_term']) : '';
$kdbarang_spesifik = isset($_POST['kdbarang_spesifik']) ? clean_input($_POST['kdbarang_spesifik']) : '';

// Set header untuk file Excel
if (!empty($kdbarang_spesifik)) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Data_Barang_{$kdbarang_spesifik}_".date('Y-m-d_H-i-s').".xls");
} else {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Data_Semua_Barang_".date('Y-m-d_H-i-s').".xls");
}
header("Pragma: no-cache");
header("Expires: 0");

// Query data tabel tambah dengan filter pencarian
$whereClauseTambah = '';
if (!empty($kdbarang_spesifik)) {
    // Jika ada kode barang spesifik, prioritaskan itu
    $whereClauseTambah = " WHERE kdbarang = '$kdbarang_spesifik'";
} elseif (!empty($keyword)) {
    $whereClauseTambah = " WHERE 
        nomor LIKE '%$keyword%' OR 
        kdbarang LIKE '%$keyword%' OR 
        namabarang LIKE '%$keyword%' OR 
        np LIKE '%$keyword%' OR 
        tanggal LIKE '%$keyword%' OR 
        satuan LIKE '%$keyword%' OR 
        jumlah LIKE '%$keyword%' OR 
        harga LIKE '%$keyword%' OR 
        totalharga LIKE '%$keyword%' OR 
        ket LIKE '%$keyword%'";
}

// Query data tabel keluar dengan filter pencarian  
$whereClauseKeluar = '';
if (!empty($kdbarang_spesifik)) {
    // Jika ada kode barang spesifik, prioritaskan itu
    $whereClauseKeluar = " WHERE kdbarang = '$kdbarang_spesifik'";
} elseif (!empty($keyword)) {
    $whereClauseKeluar = " WHERE 
        nomor LIKE '%$keyword%' OR 
        kdbarang LIKE '%$keyword%' OR 
        namabarang LIKE '%$keyword%' OR 
        np LIKE '%$keyword%' OR 
        tanggal LIKE '%$keyword%' OR 
        satuan LIKE '%$keyword%' OR 
        jumlah LIKE '%$keyword%' OR 
        harga LIKE '%$keyword%' OR 
        totalharga LIKE '%$keyword%' OR 
        ket LIKE '%$keyword%'";
}

$queryTambah = mysqli_query($conn, "SELECT * FROM tambah" . $whereClauseTambah);
$queryKeluar = mysqli_query($conn, "SELECT * FROM keluar" . $whereClauseKeluar);

// Jika ada kode barang spesifik, ambil info detail dari tabel input
$detailBarang = null;
if (!empty($kdbarang_spesifik)) {
    $queryDetail = mysqli_query($conn, "SELECT * FROM input WHERE kdbarang = '$kdbarang_spesifik' LIMIT 1");
    if (mysqli_num_rows($queryDetail) > 0) {
        $detailBarang = mysqli_fetch_assoc($queryDetail);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Excel Barang</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 30px;
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
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .detail-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .detail-info table {
            margin-bottom: 0;
        }
        .detail-info th {
            width: 25%;
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <?php if (!empty($kdbarang_spesifik) && $detailBarang): ?>
        <div class="title">DATA BARANG SPESIFIK - <?= htmlspecialchars($detailBarang['namabarang']) ?></div>
        <div class="info">Tanggal Export: <?= date('d/m/Y H:i:s') ?> WIB</div>
        
        <!-- Detail Barang -->
        <div class="detail-info">
            <div class="section-title">DETAIL BARANG</div>
            <table>
                <tr>
                    <th>Nomor</th>
                    <td><?= htmlspecialchars($detailBarang['nomor']) ?></td>
                    <th>Kode Barang</th>
                    <td><?= htmlspecialchars($detailBarang['kdbarang']) ?></td>
                </tr>
                <tr>
                    <th>Nama Barang</th>
                    <td><?= htmlspecialchars($detailBarang['namabarang']) ?></td>
                    <th>Nama Pengirim</th>
                    <td><?= htmlspecialchars($detailBarang['np']) ?></td>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <td><?= date('d/m/Y', strtotime($detailBarang['tanggal'])) ?></td>
                    <th>Satuan</th>
                    <td><?= htmlspecialchars($detailBarang['satuan']) ?></td>
                </tr>
                <tr>
                    <th>Jumlah</th>
                    <td><?= number_format($detailBarang['jumlah'], 0, ',', '.') ?></td>
                    <th>Harga</th>
                    <td>Rp <?= number_format($detailBarang['harga'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Total Harga</th>
                    <td>Rp <?= number_format($detailBarang['totalharga'], 0, ',', '.') ?></td>
                    <th>Keterangan</th>
                    <td><?= htmlspecialchars($detailBarang['ket']) ?></td>
                </tr>
            </table>
        </div>
    <?php else: ?>
        <div class="title">DATA SEMUA BARANG (MASUK & KELUAR)</div>
        <div class="info">Tanggal Export: <?= date('d/m/Y H:i:s') ?> WIB</div>
    <?php endif; ?>
    
    <!-- RIWAYAT BARANG MASUK/TAMBAH -->
    <div class="section-title">RIWAYAT BARANG MASUK</div>
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
            <?php if (mysqli_num_rows($queryTambah) > 0): ?>
                <?php $no = 1; ?>
                <?php while($data = mysqli_fetch_assoc($queryTambah)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($data['nomor']) ?></td>
                        <td><?= htmlspecialchars($data['kdbarang']) ?></td>
                        <td><?= htmlspecialchars($data['nmbarang']) ?></td>
                        <td><?= htmlspecialchars($data['np']) ?></td>
                        <td><?= date('d/m/Y', strtotime($data['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($data['satuan']) ?></td>
                        <td><?= number_format($data['jumlah'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($data['harga'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($data['totalharga'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($data['ket']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" style="text-align:center;">Tidak ada data barang masuk</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- RIWAYAT BARANG KELUAR -->
    <div class="section-title">RIWAYAT BARANG KELUAR</div>
    <table>
        <thead> 
            <tr>
                <th>No</th>
                <th>Nomor</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Nama Penerima</th>
                <th>Tanggal</th>
                <th>Satuan</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Total Harga</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($queryKeluar) > 0): ?>
                <?php $no = 1; ?>
                <?php while($data = mysqli_fetch_assoc($queryKeluar)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($data['nomor']) ?></td>
                        <td><?= htmlspecialchars($data['kdbarang']) ?></td>
                        <td><?= htmlspecialchars($data['nmbarang']) ?></td>
                        <td><?= htmlspecialchars($data['pengeluaran']) ?></td>
                        <td><?= date('d/m/Y', strtotime($data['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($data['satuan']) ?></td>
                        <td><?= number_format($data['jumlah'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($data['harga'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($data['totalharga'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($data['ket']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" style="text-align:center;">Tidak ada data barang keluar</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($kdbarang_spesifik)): ?>
        <!-- RINGKASAN -->
        <div class="section-title">RINGKASAN</div>
        <table>
            <tr>
                <th>Total Barang Masuk</th>
                <td><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tambah WHERE kdbarang = '$kdbarang_spesifik'")) ?> transaksi</td>
            </tr>
            <tr>
                <th>Total Barang Keluar</th>
                <td><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM keluar WHERE kdbarang = '$kdbarang_spesifik'")) ?> transaksi</td>
            </tr>
        </table>
    <?php endif; ?>
</body>
</html>