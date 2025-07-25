<?php
require "konek.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nomor'])) {
    $nomor = mysqli_real_escape_string($conn, $_POST['nomor']);
    
    try {
        // Dapatkan info barang
        $query_info = "SELECT nomor, kdbarang, nmbarang FROM keluar WHERE nomor = '$nomor'";
        $result_info = mysqli_query($conn, $query_info);
        
        if (!$result_info) {
            throw new Exception("Error query: " . mysqli_error($conn));
        }
        
        if (mysqli_num_rows($result_info) > 0) {
            $barang_info = mysqli_fetch_assoc($result_info);
            
            // Hapus data
            $delete_query = "DELETE FROM keluar WHERE nomor = '$nomor'";
            if (mysqli_query($conn, $delete_query)) {
                $_SESSION['message'] = "Data " . $barang_info['kdbarang'] . " - " . $barang_info['nmbarang'] . " berhasil dihapus";
            } else {
                throw new Exception("Gagal hapus: " . mysqli_error($conn));
            }
        } else {
            throw new Exception("Data tidak ditemukan");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request";
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>