<?php
require_once('../config/db.php'); // Path ke file koneksi database
require_once('../fpdf/fpdf.php'); // Path ke library FPDF

// --- Pengecekan Koneksi Database ---
// Pastikan variabel $conn sudah didefinisikan dengan benar di file db.php
// Jika $conn tidak terdefinisi atau koneksi gagal, buat PDF error dan keluar.
if (!isset($conn) || !$conn) {
    $pdf_error = new FPDF();
    $pdf_error->AddPage();
    $pdf_error->SetFont('Arial', 'B', 16);
    $pdf_error->Cell(0, 10, 'ERROR: Koneksi database gagal! Periksa file db.php Anda.', 0, 1, 'C');
    $pdf_error->Output('E', 'db_connection_error.pdf'); // 'E' untuk menampilkan error di browser
    exit;
}

// --- Ambil Tipe Laporan dari URL ---
// Ambil nilai 'tipe' dari parameter URL (contoh: export_pdf.php?tipe=balita)
// Defaultnya adalah 'balita' jika parameter 'tipe' tidak ada.
$tipe = $_GET['tipe'] ?? 'balita'; 

// --- Inisialisasi FPDF ---
// 'L' = Landscape (orientasi halaman)
// 'mm' = Milimeter (satuan pengukuran)
// 'A4' = Ukuran halaman
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage(); // Tambah halaman baru
$pdf->SetFont('Arial', 'B', 14); // Set font untuk judul

// --- Judul Laporan ---
// ucfirst() digunakan untuk mengubah huruf pertama string menjadi kapital (contoh: "balita" menjadi "Balita")
$pdf->Cell(0, 10, 'Laporan Data ' . ucfirst($tipe) . ' - Posyandu Sehat', 0, 1, 'C');
$pdf->Ln(5); // Baris kosong setelah judul

// Inisialisasi variabel statement untuk Prepared Statement
$stmt = null;

// --- Logika untuk Laporan Data Balita ---
if ($tipe === 'balita') {
    // Header Tabel Balita
    $pdf->SetFont('Arial', 'B', 10); // Font untuk header tabel
    // --- START CHANGE: Adjusted cell widths and added RT header ---
    $pdf->Cell(40, 10, 'Nama Balita', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Tgl Lahir', 1, 0, 'C');
    $pdf->Cell(20, 10, 'JK', 1, 0, 'C'); // Jenis Kelamin (slightly smaller)
    $pdf->Cell(55, 10, 'Nama Orang Tua', 1, 0, 'C'); // Slightly smaller
    $pdf->Cell(15, 10, 'RT', 1, 0, 'C'); // New RT header
    $pdf->Cell(80, 10, 'Alamat', 1, 0, 'C'); // Adjusted width for Alamat
    // --- END CHANGE ---
    $pdf->Ln(); // Pindah ke baris baru setelah header

    $pdf->SetFont('Arial', '', 9); // Font untuk isi tabel
    
    // --- START CHANGE: Added b.rt to the SELECT statement ---
    // Query Data Balita dengan Prepared Statement untuk keamanan
    $sql = "SELECT b.nama, b.tgl_lahir, b.jenis_kelamin, b.alamat, b.rt, o.nama AS nama_ortu 
            FROM balita b 
            JOIN orang_tua o ON b.id_orangtua = o.id 
            ORDER BY b.nama ASC"; // Urutkan berdasarkan nama balita
    // --- END CHANGE ---

    $stmt = $conn->prepare($sql);
    // Pengecekan jika prepared statement gagal
    if ($stmt === false) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Error menyiapkan query data balita: ' . $conn->error, 0, 1, 'C');
        $pdf->Output('I', 'laporan_error.pdf'); // Output error di PDF
        exit;
    }
    
    $stmt->execute(); // Jalankan query
    $result = $stmt->get_result(); // Ambil hasilnya

    // Loop untuk menampilkan data balita
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(40, 8, htmlspecialchars($row['nama']), 1); // htmlspecialchars untuk keamanan
            $pdf->Cell(30, 8, date('d-m-Y', strtotime($row['tgl_lahir'])), 1); // Format tanggal
            $pdf->Cell(20, 8, ($row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'), 1); // Tampilkan JK
            $pdf->Cell(55, 8, htmlspecialchars($row['nama_ortu']), 1);
            $pdf->Cell(15, 8, htmlspecialchars($row['rt']), 1, 0, 'C'); // Display RT value, centered
            
            // --- Penanganan Kolom Alamat dengan MultiCell ---
            // MultiCell digunakan untuk teks yang mungkin panjang dan butuh beberapa baris.
            // --- START CHANGE: Adjusted alamat_width ---
            $alamat_width = 80; // Lebar kolom alamat (adjusted)
            // --- END CHANGE ---
            $alamat_height_per_line = 4; // Tinggi perkiraan setiap baris teks dalam MultiCell
            
            // Simpan posisi X dan Y saat ini sebelum MultiCell
            $current_x = $pdf->GetX();
            $current_y = $pdf->GetY();
            
            // Gambar border untuk seluruh sel terlebih dahulu. 
            // Tinggi sel diset ke 8mm (tinggi standar baris lain).
            $pdf->Rect($current_x, $current_y, $alamat_width, 8); 

            // Set posisi untuk MultiCell (sedikit masuk dari border)
            $pdf->SetXY($current_x + 1, $current_y + 1); 
            // Tampilkan teks alamat tanpa border (0), karena border sudah digambar dengan Rect()
            $pdf->MultiCell($alamat_width - 2, $alamat_height_per_line, htmlspecialchars($row['alamat']), 0, 'L'); 

            // Setelah MultiCell, kursor berada di bawah teks terakhirnya.
            // Kita perlu mengembalikan kursor ke posisi yang benar untuk kolom berikutnya,
            // yaitu di ujung kolom alamat pada baris yang sama dengan tinggi standar.
            $pdf->SetXY($current_x + $alamat_width, $current_y); 
            $pdf->Ln(8); // Pindah ke baris baru dengan tinggi standar 8mm
        }
    } else {
        $pdf->Cell(0, 10, 'Tidak ada data balita ditemukan.', 1, 1, 'C');
    }

// --- Logika untuk Laporan Data Pemeriksaan ---
} elseif ($tipe === 'pemeriksaan') {
    // Header Tabel Pemeriksaan
    $pdf->SetFont('Arial', 'B', 9); // Font untuk header tabel
    $pdf->Cell(40, 10, 'Nama Balita', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Tanggal', 1, 0, 'C');
    $pdf->Cell(25, 10, 'Berat (kg)', 1, 0, 'C');
    $pdf->Cell(25, 10, 'Tinggi (cm)', 1, 0, 'C');
    $pdf->Cell(35, 10, 'Lingkar Kepala (cm)', 1, 0, 'C');
    $pdf->Cell(90, 10, 'Catatan', 1, 0, 'C');
    $pdf->Ln(); // Pindah ke baris baru

    $pdf->SetFont('Arial', '', 8); // Font untuk isi tabel
    
    // Query Data Pemeriksaan dengan Prepared Statement
    $sql = "SELECT p.tanggal, p.berat, p.tinggi, p.lingkar_kepala, p.catatan, b.nama AS nama_balita 
            FROM pemeriksaan p 
            JOIN balita b ON p.id_balita = b.id 
            ORDER BY p.tanggal DESC, b.nama ASC"; // Urutkan berdasarkan tanggal terbaru dan nama balita

    $stmt = $conn->prepare($sql);
    // Pengecekan jika prepared statement gagal
    if ($stmt === false) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Error menyiapkan query data pemeriksaan: ' . $conn->error, 0, 1, 'C');
        $pdf->Output('I', 'laporan_error.pdf'); // Output error di PDF
        exit;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    // Loop untuk menampilkan data pemeriksaan
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(40, 8, htmlspecialchars($row['nama_balita']), 1);
            $pdf->Cell(30, 8, date('d-m-Y', strtotime($row['tanggal'])), 1);
            $pdf->Cell(25, 8, number_format($row['berat'], 1), 1, 0, 'C'); // Format float dan tengah
            $pdf->Cell(25, 8, number_format($row['tinggi'], 1), 1, 0, 'C'); // Format float dan tengah
            $pdf->Cell(35, 8, number_format($row['lingkar_kepala'], 1), 1, 0, 'C'); // Format float dan tengah
            
            // --- Penanganan Kolom Catatan dengan MultiCell ---
            $catatan_width = 90; // Lebar kolom catatan
            $catatan_height_per_line = 4; // Tinggi perkiraan setiap baris teks dalam MultiCell
            
            // Simpan posisi X dan Y saat ini sebelum MultiCell
            $current_x = $pdf->GetX();
            $current_y = $pdf->GetY();
            
            // Gambar border untuk seluruh sel dengan tinggi 8mm
            $pdf->Rect($current_x, $current_y, $catatan_width, 8); 

            // Set posisi untuk MultiCell (sedikit masuk dari border)
            $pdf->SetXY($current_x + 1, $current_y + 1); 
            // Tampilkan teks catatan tanpa border (0)
            $pdf->MultiCell($catatan_width - 2, $catatan_height_per_line, htmlspecialchars($row['catatan']), 0, 'L'); 

            // Kembalikan kursor ke posisi yang benar setelah MultiCell
            $pdf->SetXY($current_x + $catatan_width, $current_y); 
            $pdf->Ln(8); // Pindah ke baris baru dengan tinggi standar 8mm
        }
    } else {
        $pdf->Cell(0, 10, 'Tidak ada data pemeriksaan ditemukan.', 1, 1, 'C');
    }

// --- Penanganan Tipe Data Tidak Dikenali ---
} else {
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Tipe data tidak dikenali. Gunakan parameter "tipe=balita" atau "tipe=pemeriksaan" pada URL.', 0, 1, 'C');
}

// --- Penutupan Koneksi ---
// Tutup prepared statement jika sudah berhasil dibuat
if ($stmt) {
    $stmt->close();
}

// Tutup koneksi database
// Pastikan $conn ada sebelum mencoba menutupnya
if (isset($conn) && $conn) { 
    $conn->close();
}

// --- Output PDF ---
// 'I' = Inline (PDF akan ditampilkan langsung di browser)
// Nama file akan menyertakan tanggal dan waktu agar unik
$filename = 'laporan_' . $tipe . '_' . date('Ymd_His') . '.pdf';
$pdf->Output('I', $filename);
exit; // Pastikan tidak ada output lain setelah ini

?>