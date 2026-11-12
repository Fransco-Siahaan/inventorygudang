<?php
// Endpoint JSON: riwayat tambah & keluar untuk satu kode barang (dipakai pada modal detail).
require_once __DIR__ . '/inc/bootstrap.php';

$kdbarang = trim($_GET['kdbarang'] ?? '');
$type     = $_GET['type'] ?? '';

if ($kdbarang === '' || !in_array($type, ['tambah', 'keluar'], true)) {
    json_out(['error' => 'Parameter tidak lengkap'], 400);
}

// Nama tabel berasal dari whitelist di atas, bukan dari input user langsung
$table = $type === 'tambah' ? 'tambah' : 'keluar';

$rows = db_fetch_all(
    $conn,
    "SELECT id, nomor, kdbarang, jumlah, satuan, tanggal, ket FROM {$table}
     WHERE kdbarang = ? ORDER BY tanggal DESC, id DESC",
    's',
    [$kdbarang]
);

json_out($rows);
