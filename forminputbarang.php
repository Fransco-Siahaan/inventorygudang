<?php
require "konek.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi dan sanitasi input
    $kdbarang = mysqli_real_escape_string($conn, trim($_POST["kdbarang"]));
    $nmbarang = mysqli_real_escape_string($conn, trim($_POST["namabarang"]));
    $namapengirim = mysqli_real_escape_string($conn, trim($_POST["np"]));
    $tanggal = mysqli_real_escape_string($conn, $_POST["tanggal"]);
    $satuan = mysqli_real_escape_string($conn, trim($_POST["satuan"]));
    $jlh = intval($_POST["jumlah"]);
    $price = floatval($_POST["harga"]);
    $keterangan = mysqli_real_escape_string($conn, trim($_POST["ket"]));
    
    // Validasi satuan yang diperbolehkan
    $allowed_units = ['set', 'pcs', 'unit', 'buah'];
    if (!in_array($satuan, $allowed_units)) {
        die(json_encode([
            'status' => 'error',
            'message' => 'Satuan tidak valid. Harus salah satu dari: ' . implode(', ', $allowed_units)
        ]));
    }
    
    // Hitung total harga
    $total = $jlh * $price;
    
    // Format satuan (misal: "5 set")
    $grup = $jlh . " " . $satuan;

    // Mulai transaksi
    mysqli_begin_transaction($conn);

    try {
        // Cek kode barang sudah ada
        $cekkdbarang = "SELECT kdbarang FROM input WHERE kdbarang = ? FOR UPDATE";
        $stmt = mysqli_prepare($conn, $cekkdbarang);
        mysqli_stmt_bind_param($stmt, "s", $kdbarang);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            throw new Exception("Kode barang sudah ada");
        }

        // Insert data baru dengan prepared statement
        $insert = "INSERT INTO input (kdbarang, namabarang, np, tanggal, satuan, jumlah, harga, totalharga, ket)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param($stmt, "sssssiids", 
            $kdbarang, 
            $nmbarang, 
            $namapengirim, 
            $tanggal, 
            $grup, 
            $jlh, 
            $price, 
            $total, 
            $keterangan
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal menambahkan data: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Data barang berhasil ditambahkan',
            'data' => [
                'kode_barang' => $kdbarang,
                'nama_barang' => $nmbarang,
                'satuan' => $grup,
                'total_harga' => $total
            ]
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Metode request tidak valid'
    ]);
}

mysqli_close($conn);
?>