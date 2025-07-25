<?php
require 'konek.php';
session_start();

// Set timezone
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nomor'])) {
    // Mulai transaksi
    mysqli_begin_transaction($conn);

    try {
        // Validasi input
        $nomor = mysqli_real_escape_string($conn, $_POST['nomor']);
        if (empty($nomor)) {
            throw new Exception("Nomor barang tidak valid");
        }

        // 1. Ambil informasi barang untuk pesan konfirmasi
        $query_info = "SELECT nomor, kdbarang, namabarang FROM input WHERE nomor = ?";
        $stmt_info = $conn->prepare($query_info);
        $stmt_info->bind_param("s", $nomor);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();

        if ($result_info->num_rows === 0) {
            throw new Exception("Data barang tidak ditemukan");
        }

        $barang = $result_info->fetch_assoc();

        // 2. Hapus data menggunakan prepared statement
        $delete_query = "DELETE FROM input WHERE nomor = ?";
        $stmt_delete = $conn->prepare($delete_query);
        $stmt_delete->bind_param("s", $nomor);
        
        if (!$stmt_delete->execute()) {
            throw new Exception("Gagal menghapus data: " . $stmt_delete->error);
        }

        // Commit transaksi jika semua berhasil
        mysqli_commit($conn);

        $_SESSION['message'] = "Data barang " . htmlspecialchars($barang['kdbarang']) . " - " . 
                              htmlspecialchars($barang['namabarang']) . " berhasil dihapus";
    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
    } finally {
        // Tutup statement jika ada
        if (isset($stmt_info)) $stmt_info->close();
        if (isset($stmt_delete)) $stmt_delete->close();
    }
} else {
    $_SESSION['error'] = "Permintaan tidak valid";
}

// Redirect kembali ke halaman sebelumnya
header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'inbarang.php'));
exit();
?>