<?php
// Endpoint: menghapus satu baris riwayat tambah stok (tabel tambah) berdasarkan id.
require_once __DIR__ . '/inc/bootstrap.php';

$redirect = $_POST['redirect'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    flash('error', 'Permintaan tidak valid.');
    redirect_local($redirect, 'inbarang.php');
}

$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$row = $id ? db_one($conn, 'SELECT kdbarang, nmbarang FROM tambah WHERE id = ?', 'i', [$id]) : null;

if (!$row) {
    flash('error', 'Data riwayat tidak ditemukan.');
} else {
    db_exec($conn, 'DELETE FROM tambah WHERE id = ?', 'i', [$id]);
    flash('success', "Riwayat tambah {$row['kdbarang']} – {$row['nmbarang']} berhasil dihapus.");
}

redirect_local($redirect, 'inbarang.php');
