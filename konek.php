<?php
// Konfigurasi koneksi database.
// Nilai default bisa ditimpa lewat environment variable (DB_HOST, DB_USER, DB_PASS, DB_NAME)
// supaya kredensial tidak perlu ditulis di kode saat deploy.

$server     = getenv('DB_HOST') ?: 'localhost';
$username   = getenv('DB_USER') ?: 'root';
$pass       = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$nmdatabase = getenv('DB_NAME') ?: 'dbiogudang';

// Error query dilempar sebagai exception supaya bisa ditangkap dengan try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($server, $username, $pass, $nmdatabase);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('[DB] Koneksi gagal: ' . $e->getMessage());
    http_response_code(500);
    die('Koneksi database gagal. Silakan hubungi administrator.');
}
