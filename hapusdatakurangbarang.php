<?php
// Endpoint: menghapus satu baris riwayat pengeluaran (tabel keluar) berdasarkan id.
require_once __DIR__ . '/inc/bootstrap.php';

$redirect = $_POST['redirect'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    flash('error', 'Permintaan tidak valid.');
    redirect_local($redirect, 'riwayatkurangbarang.php');
}

$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$row = $id ? db_one($conn, 'SELECT kdbarang, nmbarang FROM keluar WHERE id = ?', 'i', [$id]) : null;

if (!$row) {
    flash('error', 'Data riwayat tidak ditemukan.');
} else {
    db_exec($conn, 'DELETE FROM keluar WHERE id = ?', 'i', [$id]);
    flash('success', "Riwayat keluar {$row['kdbarang']} – {$row['nmbarang']} berhasil dihapus.");
}

redirect_local($redirect, 'riwayatkurangbarang.php');
