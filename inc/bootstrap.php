<?php
// Inisialisasi bersama: session, zona waktu, koneksi database, dan helper.
// Setiap halaman dan endpoint cukup memanggil file ini.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../konek.php';
require_once __DIR__ . '/functions.php';
