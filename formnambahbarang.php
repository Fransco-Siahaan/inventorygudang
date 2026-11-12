<?php
// Endpoint: menambah stok barang yang sudah terdaftar.
// Mengupdate tabel input (stok & nilai) dan mencatat riwayat di tabel tambah, dalam satu transaksi.
require_once __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['status' => 'error', 'message' => 'Metode request tidak valid'], 405);
}

$kdbarang = trim($_POST['kdbarang'] ?? '');
$supplier = trim($_POST['supplier'] ?? '');
$jumlah   = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);
$tanggal  = $_POST['tanggal'] ?? '';
$ket      = trim($_POST['ket'] ?? '');

if ($kdbarang === '') {
    json_out(['status' => 'error', 'message' => 'Pilih kode barang terlebih dahulu'], 422);
}
if ($supplier === '') {
    json_out(['status' => 'error', 'message' => 'Nama supplier/pemasok wajib diisi'], 422);
}
if ($jumlah === false || $jumlah <= 0) {
    json_out(['status' => 'error', 'message' => 'Jumlah tambahan harus lebih dari 0'], 422);
}
if (!valid_date($tanggal)) {
    json_out(['status' => 'error', 'message' => 'Tanggal tidak valid'], 422);
}

try {
    mysqli_begin_transaction($conn);

    // Kunci baris barang agar tidak ada transaksi lain yang mengubah stok bersamaan
    $barang = db_one(
        $conn,
        'SELECT nomor, namabarang, jumlah, satuan, harga, totalharga FROM input WHERE kdbarang = ? FOR UPDATE',
        's',
        [$kdbarang]
    );
    if (!$barang) {
        throw new RuntimeException('Barang tidak ditemukan dalam database');
    }

    [, $unit] = split_satuan($barang['satuan']);
    $unit = $unit !== '' ? $unit : 'set';

    $harga       = (float) $barang['harga'];
    $totalTambah = $jumlah * $harga;
    $stokBaru    = (int) $barang['jumlah'] + $jumlah;
    $totalBaru   = (float) $barang['totalharga'] + $totalTambah;
    $satuanBaru  = $stokBaru . ' ' . $unit;
    $satuanMasuk = $jumlah . ' ' . $unit;

    db_exec(
        $conn,
        'UPDATE input SET jumlah = ?, satuan = ?, totalharga = ?, tanggal = ? WHERE kdbarang = ?',
        'isdss',
        [$stokBaru, $satuanBaru, $totalBaru, $tanggal, $kdbarang]
    );

    db_exec(
        $conn,
        'INSERT INTO tambah (nomor, kdbarang, nmbarang, np, tanggal, satuan, jumlah, harga, totalharga, ket)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'isssssidds',
        [
            (int) $barang['nomor'], $kdbarang, $barang['namabarang'], $supplier,
            $tanggal, $satuanMasuk, $jumlah, $harga, $totalTambah, $ket,
        ]
    );

    mysqli_commit($conn);

    flash('success', "Stok {$kdbarang} bertambah {$jumlah} {$unit}. Stok sekarang {$stokBaru} {$unit}.");
    json_out([
        'status'  => 'success',
        'message' => 'Stok berhasil ditambahkan',
        'data'    => ['sisa_stok' => $stokBaru, 'satuan' => $unit],
    ]);
} catch (RuntimeException $e) {
    mysqli_rollback($conn);
    json_out(['status' => 'error', 'message' => $e->getMessage()], 404);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log('[formnambahbarang] ' . $e->getMessage());
    json_out(['status' => 'error', 'message' => 'Terjadi kesalahan pada server'], 500);
}
