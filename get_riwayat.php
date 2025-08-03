<?php
require "konek.php";
header('Content-Type: application/json');

// Ambil parameter dari URL
$kdbarang = isset($_GET['kdbarang']) ? $_GET['kdbarang'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Validasi parameter
if (empty($kdbarang) || empty($type)) {
    echo json_encode(['error' => 'Parameter tidak lengkap']);
    exit;
}

// Tentukan tabel berdasarkan type
$table = ($type === 'tambah') ? 'tambah' : 'keluar';

// Gunakan prepared statement untuk mencegah SQL injection
$query = "SELECT * FROM $table WHERE kdbarang = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['error' => 'Gagal mempersiapkan statement']);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $kdbarang);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Tutup statement
mysqli_stmt_close($stmt);

// Kembalikan data dalam format JSON
echo json_encode($data);
?>