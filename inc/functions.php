<?php
// Kumpulan fungsi bantu yang dipakai di seluruh aplikasi.

const SATUAN_OPSI   = ['set', 'pcs', 'unit', 'buah'];
const LOW_STOCK     = 5;   // stok di bawah / sama dengan angka ini dianggap menipis
const PER_PAGE_LIST = 10;

/* ---------- Output & format ---------- */

// Escape untuk ditampilkan di HTML (aman untuk isi tag maupun atribut)
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Format angka rupiah, contoh: Rp 1.250.000
function rupiah($nilai): string
{
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
}

// Format angka biasa dengan pemisah ribuan, contoh: 1.250
function angka($nilai): string
{
    return number_format((float) $nilai, 0, ',', '.');
}

// Format tanggal Indonesia, contoh: 12 November 2026
function tgl_indo(?string $tanggal): string
{
    if (!$tanggal) {
        return '-';
    }
    $ts = strtotime($tanggal);
    if ($ts === false) {
        return '-';
    }
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
        'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

// Pecah string satuan seperti "5 pcs" menjadi [5, "pcs"]
function split_satuan(string $satuan): array
{
    if (preg_match('/^\s*(\d+)\s*(.*)$/u', $satuan, $m)) {
        return [(int) $m[1], trim($m[2])];
    }
    return [0, trim($satuan)];
}

/* ---------- Respons & navigasi ---------- */

// Kirim respons JSON lalu hentikan script
function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Simpan pesan untuk ditampilkan sebagai notifikasi di halaman berikutnya
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_all(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

// Redirect hanya ke halaman lokal (nama file .php di folder ini).
// Mencegah open redirect lewat parameter 'redirect'.
function redirect_local(?string $target, string $fallback): never
{
    $valid = $target !== null
        && preg_match('/^[A-Za-z0-9_]+\.php(\?[A-Za-z0-9_=&%.+\-]*)?$/', $target) === 1;
    header('Location: ' . ($valid ? $target : $fallback));
    exit;
}

// Validasi tanggal format Y-m-d
function valid_date(?string $tanggal): bool
{
    if ($tanggal === null) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    return $d !== false && $d->format('Y-m-d') === $tanggal;
}

/* ---------- Database (prepared statement) ---------- */

function db_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function db_one(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_fetch_all($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

function db_scalar(mysqli $conn, string $sql, string $types = '', array $params = [])
{
    $row = db_one($conn, $sql, $types, $params);
    return $row ? reset($row) : null;
}

// Untuk INSERT / UPDATE / DELETE. Mengembalikan jumlah baris yang terpengaruh.
function db_exec(mysqli $conn, string $sql, string $types = '', array $params = []): int
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}

/* ---------- Pencarian & pagination ---------- */

// Bangun klausa WHERE pencarian (LIKE) pada daftar kolom.
// $cols harus berisi nama kolom dari kode (bukan input user).
// Nilai pencarian selalu dikirim sebagai parameter prepared statement.
function search_where(array $cols, string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'types' => '', 'params' => []];
    }
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $parts = [];
    $params = [];
    foreach ($cols as $col) {
        $parts[] = "CAST($col AS CHAR) LIKE ?";
        $params[] = $like;
    }
    return [
        'sql'    => ' WHERE (' . implode(' OR ', $parts) . ')',
        'types'  => str_repeat('s', count($params)),
        'params' => $params,
    ];
}

function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return [
        'total'  => $total,
        'per'    => $perPage,
        'pages'  => $pages,
        'page'   => $page,
        'offset' => ($page - 1) * $perPage,
    ];
}

// HTML pagination. $extra berisi parameter lain yang ikut dibawa (misalnya q).
function render_pager(array $pg, array $extra = []): string
{
    if ($pg['pages'] <= 1) {
        return '';
    }
    $self = e(basename($_SERVER['SCRIPT_NAME']));
    $url = static function (int $p) use ($self, $extra): string {
        return $self . '?' . e(http_build_query(array_merge($extra, ['page' => $p])));
    };
    $cur = $pg['page'];
    $last = $pg['pages'];
    $start = max(1, $cur - 2);
    $end = min($last, $cur + 2);

    $h = '<nav class="pager" aria-label="Navigasi halaman">';
    $h .= $cur > 1
        ? '<a href="' . $url($cur - 1) . '" aria-label="Halaman sebelumnya"><i class="fa-solid fa-chevron-left"></i></a>'
        : '<span class="dis"><i class="fa-solid fa-chevron-left"></i></span>';
    if ($start > 1) {
        $h .= '<a href="' . $url(1) . '">1</a><span>…</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $h .= $i === $cur
            ? '<span class="cur" aria-current="page">' . $i . '</span>'
            : '<a href="' . $url($i) . '">' . $i . '</a>';
    }
    if ($end < $last) {
        $h .= '<span>…</span><a href="' . $url($last) . '">' . $last . '</a>';
    }
    $h .= $cur < $last
        ? '<a href="' . $url($cur + 1) . '" aria-label="Halaman berikutnya"><i class="fa-solid fa-chevron-right"></i></a>'
        : '<span class="dis"><i class="fa-solid fa-chevron-right"></i></span>';
    $h .= '</nav>';
    return $h;
}

// Ambil nomor halaman dari query string dengan aman
function page_param(): int
{
    return max(1, (int) ($_GET['page'] ?? 1));
}

// Kolom yang bisa dicari per tabel
const COLS_INPUT   = ['nomor', 'kdbarang', 'namabarang', 'np', 'tanggal', 'satuan', 'jumlah', 'harga', 'totalharga', 'ket'];
const COLS_TAMBAH  = ['nomor', 'kdbarang', 'nmbarang', 'np', 'tanggal', 'satuan', 'jumlah', 'harga', 'totalharga', 'ket'];
const COLS_KELUAR  = ['nomor', 'kdbarang', 'nmbarang', 'pengeluaran', 'tanggal', 'satuan', 'jumlah', 'harga', 'totalharga', 'ket'];
