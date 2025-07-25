<?php
require "konek.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi input
    $nomor = isset($_POST["nomor"]) ? trim($_POST['nomor']) : null;
    $idbarang = isset($_POST["kdbarang"]) ? trim($_POST['kdbarang']) : null;
    $jlhbarang = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
    $harga = isset($_POST['harga']) ? (int)$_POST['harga'] : 0;

    if (empty($nomor) || empty($idbarang) || $jlhbarang <= 0 || $harga <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Semua field harus diisi dengan benar']);
        exit;
    }

    // Hitung total harga
    $totalharga = $jlhbarang * $harga;

    mysqli_begin_transaction($conn);

    try {
        // 1. Verifikasi kombinasi nomor dan kdbarang ada di tabel input
        $check = "SELECT jumlah, satuan, totalharga FROM input WHERE nomor = ? AND kdbarang = ? FOR UPDATE";
        $stmt = mysqli_prepare($conn, $check);
        mysqli_stmt_bind_param($stmt, "ss", $nomor, $idbarang);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 0) {
            throw new Exception("Kombinasi nomor dan kode barang tidak valid");
        }

        $barang = mysqli_fetch_assoc($result);
        $stok_sekarang = $barang['jumlah'];
        $totalharga_sekarang = $barang['totalharga'];

        // Ekstrak angka dari satuan
        $satuan_numerik = (int)$barang['satuan'];
        $satuan_text = trim(str_replace($satuan_numerik, '', $barang['satuan'])) ?: ' set';

        // 2. Validasi stok cukup
        if ($stok_sekarang < $jlhbarang) {
            throw new Exception("Stok tidak mencukupi. Stok tersedia: $stok_sekarang$satuan_text");
        }

        // 3. Hitung satuan baru dan total harga baru
        $satuan_baru = $stok_sekarang - $jlhbarang;
        $totalharga_baru = $totalharga_sekarang - $totalharga;
        $totalharga_baru = max(0, $totalharga_baru); // Pastikan tidak negatif

        // 4. Update stok di tabel input
        $update = "UPDATE input SET 
                  jumlah = ?, 
                  satuan = CONCAT(?, ?), 
                  totalharga = ?
                  WHERE nomor = ? AND kdbarang = ?";
        $stmt = mysqli_prepare($conn, $update);
        mysqli_stmt_bind_param($stmt, "ississ", 
            $satuan_baru, 
            $satuan_baru, 
            $satuan_text, 
            $totalharga_baru,
            $nomor,
            $idbarang
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal update stok: " . mysqli_error($conn));
        }

        // 5. Simpan ke tabel keluar
        $insert = "INSERT INTO keluar (
            nomor,
            kdbarang, 
            nmbarang, 
            pengeluaran, 
            satuan, 
            jumlah, 
            harga, 
            totalharga,
            tanggal, 
            ket
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $satuan_keluar = $jlhbarang . $satuan_text;

        $stmt = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param(
            $stmt,
            "sssssiisss",
            $nomor,
            $idbarang,
            $_POST['nmbarang'],
            $_POST['pengeluaran'],
            $satuan_keluar,
            $jlhbarang,
            $harga,
            $totalharga,
            $_POST['tanggal'],
            $_POST['ket']
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal mencatat pengeluaran: " . mysqli_error($conn));
        }

        mysqli_commit($conn);

        echo json_encode([
            'status' => 'success',
            'message' => 'Data pengeluaran berhasil disimpan',
            'sisa_stok' => $satuan_baru,
            'satuan' => $satuan_text,
            'sisa_totalharga' => $totalharga_baru
        ]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid']);
}

mysqli_close($conn);
?>