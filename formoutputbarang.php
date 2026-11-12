<?php
// Endpoint: mencatat pengeluaran barang.
// Stok dicek di server (bukan hanya di browser), harga diambil dari database, dan semuanya dalam satu transaksi.
require_once __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['status' => 'error', 'message' => 'Metode request tidak valid'], 405);
}

$nomor       = filter_var($_POST['nomor'] ?? null, FILTER_VALIDATE_INT);
$kdbarang    = trim($_POST['kdbarang'] ?? '');
$pengeluaran = trim($_POST['pengeluaran'] ?? '');
$jumlah      = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);
$tanggal     = $_POST['tanggal'] ?? '';
$ket         = trim($_POST['ket'] ?? '');

if ($nomor === false || $nomor <= 0 || $kdbarang === '') {
    json_out(['status' => 'error', 'message' => 'Pilih barang terlebih dahulu'], 422);
}
if ($pengeluaran === '') {
    json_out(['status' => 'error', 'message' => 'Kolom "Keluar ke" wajib diisi'], 422);
}
if ($jumlah === false || $jumlah <= 0) {
    json_out(['status' => 'error', 'message' => 'Jumlah harus lebih dari 0'], 422);
}
if (!valid_date($tanggal)) {
    json_out(['status' => 'error', 'message' => 'Tanggal tidak valid'], 422);
}

try {
    mysqli_begin_transaction($conn);

    // Kunci baris agar stok tidak bisa terkurangi dua kali secara bersamaan
    $barang = db_one(
        $conn,
        'SELECT nomor, namabarang, jumlah, satuan, harga, totalharga
         FROM input WHERE nomor = ? AND kdbarang = ? FOR UPDATE',
        'is',
        [$nomor, $kdbarang]
    );
    if (!$barang) {
        throw new RuntimeException('Kombinasi nomor dan kode barang tidak valid');
    }

    [, $unit] = split_satuan($barang['satuan']);
    $unit = $unit !== '' ? $unit : 'set';

    $stokSekarang = (int) $barang['jumlah'];
    if ($stokSekarang < $jumlah) {
        throw new RuntimeException("Stok tidak mencukupi. Stok tersedia: {$stokSekarang} {$unit}");
    }

    $harga      = (float) $barang['harga'];
    $totalKeluar = $jumlah * $harga;
    $stokBaru   = $stokSekarang - $jumlah;
    $totalBaru  = max(0.0, (float) $barang['totalharga'] - $totalKeluar);

    db_exec(
        $conn,
        'UPDATE input SET jumlah = ?, satuan = ?, totalharga = ? WHERE nomor = ? AND kdbarang = ?',
        'isdis',
        [$stokBaru, $stokBaru . ' ' . $unit, $totalBaru, $nomor, $kdbarang]
    );

    db_exec(
        $conn,
        'INSERT INTO keluar (nomor, kdbarang, nmbarang, pengeluaran, satuan, jumlah, harga, totalharga, tanggal, ket)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'issssiddss',
        [
            $nomor, $kdbarang, $barang['namabarang'], $pengeluaran,
            $jumlah . ' ' . $unit, $jumlah, $harga, $totalKeluar, $tanggal, $ket,
        ]
    );

    mysqli_commit($conn);

    flash('success', "Pengeluaran {$kdbarang} sebanyak {$jumlah} {$unit} berhasil dicatat. Sisa stok {$stokBaru} {$unit}.");
    json_out([
        'status'    => 'success',
        'message'   => 'Data pengeluaran berhasil disimpan',
        'sisa_stok' => $stokBaru,
        'satuan'    => $unit,
    ]);
} catch (RuntimeException $e) {
    mysqli_rollback($conn);
    json_out(['status' => 'error', 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log('[formoutputbarang] ' . $e->getMessage());
    json_out(['status' => 'error', 'message' => 'Terjadi kesalahan pada server'], 500);
}
