<?php
require "konek.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kdbarang = isset($_POST["kdbarang"]) ? trim($_POST['kdbarang']) : null;
    $nmbarang = isset($_POST["nmbarang"]) ? trim($_POST['nmbarang']) : null;
    $supplier = isset($_POST["supplier"]) ? trim($_POST['supplier']) : null;
    $tanggal = isset($_POST["tanggal"]) ? $_POST['tanggal'] : date('Y-m-d');
    $satuan_form = isset($_POST["satuan"]) ? trim($_POST['satuan']) : '';
    $jlh = isset($_POST["jumlah"]) ? (int)$_POST['jumlah'] : 0;
    $harga = isset($_POST["harga"]) ? (int)$_POST['harga'] : 0;
    $keterangan = isset($_POST["ket"]) ? trim($_POST['ket']) : '';

    if (empty($kdbarang) || empty($nmbarang) || $jlh <= 0 || $harga <= 0) {
        die(json_encode(['status' => 'error', 'message' => 'Input tidak valid']));
    }

    $totalharga = $jlh * $harga;

    mysqli_begin_transaction($conn);

    try {
        // Ambil stok & nomor dari input
        $query = "SELECT jumlah, satuan, totalharga, nomor FROM input WHERE kdbarang = ? FOR UPDATE";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $kdbarang);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!$result || mysqli_num_rows($result) == 0) {
            throw new Exception("Barang tidak ditemukan dalam database");
        }

        $barang = mysqli_fetch_assoc($result);
        $stok_sekarang = $barang['jumlah'];
        $totalharga_sekarang = $barang['totalharga'];
        $nomor_input = $barang['nomor'];

        // Ambil satuan teks
        $satuan_numerik = (int)$barang['satuan'];
        $satuan_text = str_replace($satuan_numerik, '', $barang['satuan']);

        if (empty(trim($satuan_text))) {
            preg_match('/\d+\s*(.*)/', $satuan_form, $matches);
            $satuan_text = isset($matches[1]) ? ' ' . trim($matches[1]) : '';
        }

        // Hitung stok dan total harga baru
        $stok_baru = $stok_sekarang + $jlh;
        $totalharga_baru = $totalharga_sekarang + $totalharga;

        // Update tabel input
        $update = "UPDATE input SET 
                  jumlah = ?, 
                  satuan = CONCAT(?, ?), 
                  totalharga = ?,
                  tanggal = ?
                  WHERE kdbarang = ?";
        $stmt = mysqli_prepare($conn, $update);
        mysqli_stmt_bind_param($stmt, "ississ",
            $stok_baru,
            $stok_baru,
            $satuan_text,
            $totalharga_baru,
            $tanggal,
            $kdbarang
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal update stok: " . mysqli_error($conn));
        }

        // Simpan ke tabel tambah (dengan nomor dari input)
        $insert = "INSERT INTO tambah (
            nomor,
            kdbarang, 
            nmbarang, 
            np, 
            tanggal, 
            satuan, 
            jumlah, 
            harga, 
            totalharga,
            ket
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $satuan_masuk = $jlh . $satuan_text;

        $stmt = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssiiss",
            $nomor_input,        // <== tambahkan ini!
            $kdbarang,
            $nmbarang,
            $supplier,
            $tanggal,
            $satuan_masuk,
            $jlh,
            $harga,
            $totalharga,
            $keterangan
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal mencatat penerimaan: " . mysqli_error($conn));
        }

        mysqli_commit($conn);

        echo json_encode([
            'status' => 'success',
            'message' => 'Stok berhasil ditambahkan',
            'data' => [
                'kode_barang' => $kdbarang,
                'ditambahkan' => $jlh,
                'satuan' => trim($satuan_text),
                'sisa_stok' => $stok_baru,
                'sisa_totalharga' => $totalharga_baru,
                'total_harga_tambah' => $totalharga
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
