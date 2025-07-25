<?php
session_start();
require "konek.php";
require_once 'vendor/autoload.php';

// Periksa koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Fungsi terbilang yang lebih baik dengan format yang benar
function terbilang($x) {
    $angka = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    
    if ($x < 0) {
        return "Minus " . terbilang(abs($x));
    }
    
    if ($x < 12) {
        return $angka[$x];
    } elseif ($x < 20) {
        return terbilang($x - 10) . " Belas";
    } elseif ($x < 100) {
        return terbilang(floor($x / 10)) . " Puluh " . terbilang($x % 10);
    } elseif ($x < 200) {
        return "Seratus " . terbilang($x - 100);
    } elseif ($x < 1000) {
        return terbilang(floor($x / 100)) . " Ratus " . terbilang($x % 100);
    } elseif ($x < 2000) {
        return "Seribu " . terbilang($x - 1000);
    } elseif ($x < 1000000) {
        return terbilang(floor($x / 1000)) . " Ribu " . terbilang($x % 1000);
    } elseif ($x < 1000000000) {
        return terbilang(floor($x / 1000000)) . " Juta " . terbilang($x % 1000000);
    } elseif ($x < 1000000000000) {
        return terbilang(floor($x / 1000000000)) . " Milyar " . terbilang($x % 1000000000);
    } else {
        return "Angka terlalu besar";
    }
}

// Ambil parameter pencarian dari POST atau SESSION
$search_term = isset($_POST['search_term']) ? $_POST['search_term'] : 
              (isset($_SESSION['saved_search']) ? $_SESSION['saved_search'] : '');

// Buat query dengan filter pencarian jika ada
$whereClause = '';
if (!empty($search_term)) {
    $whereClause = " WHERE 
        kdbarang LIKE '%$search_term%' OR 
        namabarang LIKE '%$search_term%' OR 
        np LIKE '%$search_term%' OR 
        tanggal LIKE '%$search_term%' OR 
        satuan LIKE '%$search_term%' OR 
        jumlah LIKE '%$search_term%' OR 
        harga LIKE '%$search_term%' OR 
        totalharga LIKE '%$search_term%' OR 
        ket LIKE '%$search_term%'";
}

$query = "SELECT * FROM input" . $whereClause . " ORDER BY tanggal DESC";
$result = mysqli_query($conn, $query);

// Ambil data penerima dari hasil query (ambil dari baris pertama)
$penerima = 'Tidak ada data penerima'; // Default jika tidak ada data
$telepon = 'Tidak ada data telepon';   // Default jika tidak ada data
if ($row = mysqli_fetch_assoc($result)) {
    $penerima = $row['np']; // Kolom 'np' adalah nama penerima
    $telepon = $row['telepon'] ?? '081264272544'; // Gunakan data telepon jika ada, else default
    // Kembalikan pointer hasil query ke awal agar bisa diproses untuk tabel
    mysqli_data_seek($result, 0);
}

// Buat objek PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set dokumen
$pdf->SetCreator('PT. ARGATEK TORSADA GUNA');
$pdf->SetAuthor('PT. ARGATEK TORSADA GUNA');
$pdf->SetTitle('Invoice Barang');
$pdf->SetSubject('Invoice');

// Margin
$pdf->SetMargins(15, 15, 15);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// === Header Perusahaan ===
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 5, 'PT. ARGATEK TORSADA GUNA', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Jl. H. Adam Malik No. 74 C Pematang Siantar', 0, 1, 'C');
$pdf->Cell(0, 5, 'Telp. +62-622-7433585, +62 822-7793-9298', 0, 1, 'C');
$pdf->Ln(10);

// === Judul INVOICE ===
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 8, 'INVOICE', 0, 1, 'C');
$pdf->Ln(5);

// === Informasi Invoice ===
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 5, 'Nomor', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(40, 5, '1451595', 0, 0);
$pdf->Cell(30, 5, 'Kepada', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(0, 5, $penerima, 0, 1); // Menampilkan data penerima dari database

$pdf->Cell(40, 5, 'Tanggal', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(40, 5, date('d-m-Y'), 0, 0);
$pdf->Cell(30, 5, 'PO/SPK', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(0, 5, '01451595', 0, 1);

$pdf->Cell(40, 5, 'Batas Akhir Pembayaran', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(40, 5, date('d-m-Y', strtotime('+7 days')), 0, 0);
$pdf->Cell(30, 5, 'up.', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(0, 5, 'Paris Sianturi', 0, 1);

$pdf->Cell(40, 5, '', 0, 0);
$pdf->Cell(5, 5, '', 0, 0);
$pdf->Cell(40, 5, '', 0, 0);
$pdf->Cell(30, 5, 'Telp/HP', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(0, 5, $telepon, 0, 1); // Menampilkan data telepon dari database

$pdf->Cell(40, 5, 'ID-PEL', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(0, 5, '207721529', 0, 1);
$pdf->Ln(10);

// === Tabel Item ===
$pdf->SetFont('helvetica', 'B', 10);
$header = array('No', 'Kode Barang', 'NamaBarang', 'Jumlah', 'Satuan', 'Harga', 'Subtotal');
$widths = array(10, 30, 50, 20, 20, 30, 30);

// Header tabel dengan background
$pdf->SetFillColor(220, 220, 220);
foreach ($header as $key => $col) {
    $pdf->Cell($widths[$key], 7, $col, 1, 0, 'C', 1);
}
$pdf->Ln();

// Isi Tabel
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(255);
$no = 1;
$total = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $subtotal = $row['jumlah'] * $row['harga'];
    $total += $subtotal;
    
    $pdf->Cell($widths[0], 6, $no, 'LR', 0, 'C');
    $pdf->Cell($widths[1], 6, $row['kdbarang'], 'LR', 0, 'L');
    $pdf->Cell($widths[2], 6, $row['namabarang'], 'LR', 0, 'L');
    $pdf->Cell($widths[3], 6, $row['jumlah'], 'LR', 0, 'C');
    $pdf->Cell($widths[4], 6, $row['satuan'], 'LR', 0, 'C');
    $pdf->Cell($widths[5], 6, 'Rp. '.number_format($row['harga'], 0, ',', '.'), 'LR', 0, 'R');
    $pdf->Cell($widths[6], 6, 'Rp. '.number_format($subtotal, 0, ',', '.'), 'LR', 1, 'R');
    
    $no++;
}

// Garis penutup tabel
$pdf->Cell(array_sum($widths), 0, '', 'T');
$pdf->Ln(8);

// Total Pembayaran
$terbilang = terbilang($total) . ' Rupiah';
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(120, 7, 'Terbilang: ' . $terbilang, 0, 0);
$pdf->Ln(7);
$pdf->Cell(120, 7, '', 0, 0);
$pdf->Cell(30, 7, 'JUMLAH : Rp. '.number_format($total, 0, ',', '.'), 0, 1, 'R');

$pdf->Cell(120, 7, '', 0, 0);
$pdf->Cell(30, 7, 'Sudah Terbayar : Rp. 0', 0, 1, 'R');

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(120, 10, '', 0, 0);
$pdf->Cell(30, 10, 'TOTAL TAGIHAN : Rp. '.number_format($total, 0, ',', '.'), 0, 1, 'R');
$pdf->Ln(15);

// Informasi Pembayaran
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 5, 'Silahkan Lakukan Pembayaran melalui :'."\n".
                     '= BNI No.Rek: 144.8815.702 a/n. PT. ARGATEK TORSADA GUNA'."\n".
                     '= BRI No.Rek: 208.4010.0035.2300 a/n. PT. ARGATEK TORSADA GUNA'."\n".
                     '= MANDIRI No.Rek: 107.00.2127688.8 a/n. PT. ARGATEK TORSADA GUNA'."\n".
                     '= BCA No.Rek: 8201.134.404 a/n. PT. ARGATEK TORSADA GUNA'."\n".
                     '= Kasir/Loket Alamat: Jl. H. Adam Malik No. 74-C Pematang Siantar', 0, 'L');
$pdf->Ln(15);

// Tanda tangan
$pdf->Cell(0, 5, 'Pematang Siantar, '.date('d-m-Y'), 0, 1, 'R');
$pdf->Cell(0, 20, '', 0, 1);
$pdf->Cell(0, 5, 'Administrasi & Keuangan,', 0, 1, 'R');
$pdf->Cell(0, 20, '', 0, 1);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'EVAN GINOLA Sijabat', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, 'NIP. 1415788248', 0, 1, 'R');
$pdf->Ln(10);

// Catatan kaki
$pdf->SetFont('helvetica', 'I', 8);
$pdf->MultiCell(0, 5, 'JIKA BAYAR MELALUI TRANSFER BANK, MOHON ISI BERITA : 1451595 atau KIRIMKAN COPY BUKTI TRANSFER KEPADA KAMI', 0, 'C');
$pdf->SetFont('helvetica', 'BI', 8);
$pdf->MultiCell(0, 5, 'PERHATIAN! Harap dilunasi sebelum Batas Akhir Pembayaran agar terhindar dari Sanksi Denda - TERIMA KASIH', 0, 'C');

// Output PDF
$pdf->Output('invoice.pdf', 'I');
?>