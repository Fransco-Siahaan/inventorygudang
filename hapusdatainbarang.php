<?php
require 'konek.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nomor'])) {
    $nomor = mysqli_real_escape_string($conn, $_POST['nomor']);
    $kdbarang = mysqli_real_escape_string($conn, $_POST['kdbarang']);

    // Mulai transaksi
    mysqli_begin_transaction($conn);

    try {
        // Ambil informasi barang berdasarkan nomor
        $query_info = "SELECT nomor, kdbarang, nmbarang FROM tambah WHERE nomor = '$nomor'";
        $result_info = mysqli_query($conn, $query_info);

        if (!$result_info || mysqli_num_rows($result_info) === 0) {
            throw new Exception("Data dengan nomor $nomor tidak ditemukan.");
        }

        $barang = mysqli_fetch_assoc($result_info);

        // Hapus dari tabel tambah berdasarkanomor
        $delete_query = "DELETE FROM tambah WHERE nomor = '$nomor'";
        if (!mysqli_query($conn, $delete_query)) {
            throw new Exception("Gagal menghapus data: " . mysqli_error($conn));
        }

        // Commit transaksi
        mysqli_commit($conn);

        $_SESSION['message'] = "Data barang " . $barang['kdbarang'] . " - " . $barang['nmbarang'] . " berhasil dihapus.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Permintaan tidak valid.";
}

// Redirect ke halaman sebelumnya
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>