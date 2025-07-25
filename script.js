// Data barang dari PHP ke JavaScript
const barangData = <?= json_encode($barang) ?>;

function updateBarangInfo() {
    const kodeBarang = document.getElementById('kdbarang').value;
    const nmBarang = document.getElementById('nmbarang');
    const satuan = document.getElementById('satuan');
    const harga = document.getElementById('harga');
    
    if (kodeBarang && barangData[kodeBarang]) {
        const barang = barangData[kodeBarang];
        nmBarang.value = barang.namabarang;
        satuan.value = barang.satuan;
        harga.value = barang.harga;
    } else {
        nmBarang.value = '';
        satuan.value = '';
        harga.value = '';
    }
}

// Inisialisasi awal jika ada kode barang terpilih
document.addEventListener('DOMContentLoaded', function() {
    updateBarangInfo();
    
    // Set focus ke first input
    document.getElementById('kdbarang').focus();
});