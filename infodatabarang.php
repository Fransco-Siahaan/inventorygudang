<?php
require "konek.php";
session_start();
date_default_timezone_set('Asia/Jakarta');

$nomor = isset($_POST['nomor']) ? $_POST['nomor'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Info Data Barang</title>
    <style>
        /* Popup Styles */
        #popupContainer {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .popup-content {
            background: #fff;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
            max-width: 90%;
            max-height: 85%;
            overflow: auto;
            position: relative;
        }
        #closePopupBtn {
            position: absolute;
            top: 10px;
            right: 15px;
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #00ccbb;
        }
        .filter-select {
            padding: 5px 10px;
            margin-bottom: 15px;
        }
        #showPopupBtn {
            padding: 10px 15px;
            background-color: #2980b9;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<!-- Tombol untuk membuka popup -->
<button id="showPopupBtn">Lihat Info Barang</button>

<!-- Popup -->
<div id="popupContainer">
    <div class="popup-content">
        <button id="closePopupBtn">Tutup</button>
        <h2>Info Data Barang: <?= htmlspecialchars($nomor) ?></h2>

        <!-- Dropdown Filter -->
        <select id="dataFilter" class="filter-select">
            <option value="tambah">Barang Tambah</option>
            <option value="keluar">Barang Keluar</option>
        </select>

        <!-- Tabel Barang Tambah -->
        <div id="tabelTambah">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $queryTambah = mysqli_query($conn, "SELECT * FROM tambah WHERE nomor='$nomor'");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($queryTambah)) {
                        echo "<tr>
                            <td>{$no}</td>
                            <td>{$row['nomor']}</td>
                            <td>{$row['kdbarang']}</td>
                            <td>{$row['nmbarang']}</td>
                            <td>{$row['jumlah']}</td>
                            <td>{$row['tanggal']}</td>
                            <td>{$row['ket']}</td>
                        </tr>";
                        $no++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Tabel Barang Keluar -->
        <div id="tabelKeluar" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $queryKeluar = mysqli_query($conn, "SELECT * FROM keluar WHERE nomor='$nomor'");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($queryKeluar)) {
                        echo "<tr>
                            <td>{$no}</td>
                            <td>{$row['nomor']}</td>
                            <td>{$row['kdbarang']}</td>
                            <td>{$row['nmbarang']}</td>
                            <td>{$row['jumlah']}</td>
                            <td>{$row['tanggal']}</td>
                            <td>{$row['ket']}</td>
                        </tr>";
                        $no++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Script untuk handle popup & filter -->
<script>
    document.getElementById("showPopupBtn").addEventListener("click", function () {
        document.getElementById("popupContainer").style.display = "block";
    });

    document.getElementById("closePopupBtn").addEventListener("click", function () {
        document.getElementById("popupContainer").style.display = "none";
    });

    document.getElementById("dataFilter").addEventListener("change", function () {
        var value = this.value;
        document.getElementById("tabelTambah").style.display = (value === "tambah") ? "block" : "none";
        document.getElementById("tabelKeluar").style.display = (value === "keluar") ? "block" : "none";
    });
</script>

</body>
</html>
