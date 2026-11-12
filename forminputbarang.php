<?php
// Endpoint: mendaftarkan barang baru ke tabel input.
// Diakses lewat fetch() dari masuk.php, mengembalikan JSON.
require_once __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['status' => 'error', 'message' => 'Metode request tidak valid'], 405);
}

$kdbarang   = trim($_POST['kdbarang'] ?? '');
$namabarang = trim($_POST['namabarang'] ?? '');
$pengirim   = trim($_POST['np'] ?? '');
$satuan     = trim($_POST['satuan'] ?? '');
$jumlah     = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);
$harga      = filter_var($_POST['harga'] ?? null, FILTER_VALIDATE_FLOAT);
$tanggal    = $_POST['tanggal'] ?? '';
$ket        = trim($_POST['ket'] ?? '');

// Validasi input
if ($kdbarang === '' || mb_strlen($kdbarang) > 50) {
    json_out(['status' => 'error', 'message' => 'Kode barang wajib diisi (maksimal 50 karakter)'], 422);
}
if ($namabarang === '' || $pengirim === '') {
    json_out(['status' => 'error', 'message' => 'Nama barang dan nama pengirim wajib diisi'], 422);
}
if (!in_array($satuan, SATUAN_OPSI, true)) {
    json_out(['status' => 'error', 'message' => 'Satuan tidak valid'], 422);
}
if ($jumlah === false || $jumlah <= 0) {
    json_out(['status' => 'error', 'message' => 'Jumlah harus berupa angka lebih dari 0'], 422);
}
if ($harga === false || $harga < 0) {
    json_out(['status' => 'error', 'message' => 'Harga tidak valid'], 422);
}
if (!valid_date($tanggal)) {
    json_out(['status' => 'error', 'message' => 'Tanggal tidak valid'], 422);
}

$stokAwal   = $jumlah . ' ' . $satuan;
$totalHarga = $jumlah * $harga;

try {
    $affected = db_exec(
        $conn,
        'INSERT INTO input (kdbarang, namabarang, np, tanggal, satuan, jumlah, harga, totalharga, ket)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'sssssidds',
        [$kdbarang, $namabarang, $pengirim, $tanggal, $stokAwal, $jumlah, $harga, $totalHarga, $ket]
    );

    flash('success', "Barang {$kdbarang} – {$namabarang} berhasil ditambahkan.");
    json_out([
        'status'  => 'success',
        'message' => 'Data barang berhasil ditambahkan',
        'data'    => ['kode_barang' => $kdbarang, 'satuan' => $stokAwal, 'total_harga' => $totalHarga],
    ]);
} catch (mysqli_sql_exception $e) {
    // 1062 = duplicate entry (kode barang sudah ada karena kolom kdbarang UNIQUE)
    if ((int) $e->getCode() === 1062) {
        json_out(['status' => 'error', 'message' => "Kode barang \"{$kdbarang}\" sudah digunakan"], 409);
    }
    error_log('[forminputbarang] ' . $e->getMessage());
    json_out(['status' => 'error', 'message' => 'Terjadi kesalahan pada server'], 500);
}
