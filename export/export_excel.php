<?php
// Include database connection
include '../config/db.php';

// Get the report type from the URL, default to 'balita'
$tipe = $_GET['tipe'] ?? 'balita';

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_{$tipe}.xls");

// =====================
// TIPE: Pemeriksaan
// =====================
if ($tipe == 'pemeriksaan') {
    $data = $conn->query("
        SELECT p.*, b.nama AS nama_balita 
        FROM pemeriksaan p 
        JOIN balita b ON p.id_balita = b.id 
        ORDER BY p.tanggal DESC
    ");
    echo "Nama Balita\tTanggal\tBerat\tTinggi\tLingkar Kepala\tCatatan\n";
    while ($r = $data->fetch_assoc()) {
        echo "{$r['nama_balita']}\t{$r['tanggal']}\t{$r['berat']}\t{$r['tinggi']}\t{$r['lingkar_kepala']}\t{$r['catatan']}\n";
    }
    exit;
}

// =====================
// TIPE: Laporan Bulanan
// =====================
elseif ($tipe == 'laporan') {
    $data = $conn->query("
        SELECT 
            DATE_FORMAT(p.tanggal, '%Y-%m') AS bulan,
            COUNT(p.id) AS total_pemeriksaan,
            COUNT(DISTINCT p.id_balita) AS balita_diperiksa,
            ROUND(AVG(p.berat), 2) AS rata_berat,
            ROUND(AVG(p.tinggi), 2) AS rata_tinggi
        FROM pemeriksaan p
        GROUP BY bulan
        ORDER BY bulan DESC
    ");
    echo "Bulan\tTotal Pemeriksaan\tBalita Diperiksa\tRata Berat (kg)\tRata Tinggi (cm)\n";
    while ($r = $data->fetch_assoc()) {
        echo "{$r['bulan']}\t{$r['total_pemeriksaan']}\t{$r['balita_diperiksa']}\t{$r['rata_berat']}\t{$r['rata_tinggi']}\n";
    }
    exit;
}

// =====================
// Default: Balita (with RT column added)
// =====================
// --- START CHANGE ---
// Added 'b.rt' to the SELECT statement
$data = $conn->query("SELECT b.*, o.nama AS nama_ortu FROM balita b JOIN orang_tua o ON b.id_orangtua = o.id");
// Added 'RT' to the header row
echo "Nama\tTgl Lahir\tJK\tOrang Tua\tAlamat\tRT\n";
while ($r = $data->fetch_assoc()) {
    // Added '{$r['rt']}' to the data row
    echo "{$r['nama']}\t{$r['tgl_lahir']}\t{$r['jenis_kelamin']}\t{$r['nama_ortu']}\t{$r['alamat']}\t{$r['rt']}\n";
}
// --- END CHANGE ---
?>