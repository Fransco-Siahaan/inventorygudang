<?php
// Endpoint: menghapus barang dari daftar stok (tabel input) berdasarkan nomor.
// Riwayat tambah/keluar tidak ikut dihapus.
require_once __DIR__ . '/inc/bootstrap.php';

$redirect = $_POST['redirect'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['nomor'])) {
    flash('error', 'Permintaan tidak valid.');
    redirect_local($redirect, 'informasimasukbarang.php');
}

$nomor = filter_var($_POST['nomor'], FILTER_VALIDATE_INT);
$barang = $nomor ? db_one($conn, 'SELECT kdbarang, namabarang FROM input WHERE nomor = ?', 'i', [$nomor]) : null;

if (!$barang) {
    flash('error', 'Data barang tidak ditemukan.');
} else {
    db_exec($conn, 'DELETE FROM input WHERE nomor = ?', 'i', [$nomor]);
    flash('success', "Barang {$barang['kdbarang']} – {$barang['namabarang']} berhasil dihapus.");
}

redirect_local($redirect, 'informasimasukbarang.php');
