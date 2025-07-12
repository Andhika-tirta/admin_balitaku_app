<?php
session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../config/paths.php'; 
include '../../config/db.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_app_url . "login.php"); 
    exit;
}

if (!isset($conn) || !$conn) {
    die("Error: Koneksi database belum terbentuk atau gagal.");
}

$stmt = $conn->prepare("
    SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
           ROUND(AVG(berat), 2) AS rata_berat,
           ROUND(AVG(tinggi), 2) AS rata_tinggi
    FROM pemeriksaan
    GROUP BY bulan
    ORDER BY bulan ASC
");

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->execute();
$query_result = $stmt->get_result();

$bulan = [];
$berat = [];
$tinggi = [];

while ($row = $query_result->fetch_assoc()) {
    $bulan[] = date('M Y', strtotime($row['bulan']));
    $berat[] = (float)$row['rata_berat'];
    $tinggi[] = (float)$row['rata_tinggi'];
}

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Statistik Pertumbuhan - POSYANDU BALITAKU</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
          --primary: #2a9d8f;
          --secondary: #264653;
          --accent: #e9c46a;
          --light: #f8f9fa;
          --danger: #e76f51;
          --blue-chart: #2196F3;
          --green-chart: #4CAF50;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f8f7;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        header {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            margin: 0;
            font-size: 24px;
            color: white;
        }
        h2 {
            color: var(--secondary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo i {
            font-size: 2rem; 
            margin-right: 15px; 
            color: white;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 500px;
            width: 100%;
            margin-top: 30px;
        }
        .info-box {
            background-color: var(--light);
            border-left: 4px solid var(--primary);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        .export-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            white-space: nowrap;
        }
        .btn:hover {
            background-color: #217a70;
        }
        .btn.back-to-dashboard {
            background-color: #4CAF50;
        }
        .btn.back-to-dashboard:hover {
            background-color: #45a049;
        }
        footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-baby-carriage"></i> <h1>POSYANDU BALITAKU</h1>
            </div>
            <div>
                Selamat Datang, Administrator
            </div>
        </header>
        
        <h2>📈 Statistik Pertumbuhan Balita (Kurva)</h2>
        
        <div class="info-box">
            <p>Grafik kurva ini menampilkan perkembangan rata-rata berat dan tinggi badan balita. Garis biru menunjukkan berat badan (kg) dan garis hijau menunjukkan tinggi badan (cm).</p>
        </div>
        
        <div class="export-buttons">
            <a href="<?= $base_url_admin ?>index.php" class="btn back-to-dashboard">⬅️ Kembali ke Dashboard</a>
        </div>
        
        <div class="chart-container">
            <canvas id="chartPertumbuhan"></canvas>
        </div>
        
        <script>
            const blueChartColor = getComputedStyle(document.documentElement).getPropertyValue('--blue-chart').trim();
            const greenChartColor = getComputedStyle(document.documentElement).getPropertyValue('--green-chart').trim();
            const poppinsFont = 'Poppins, sans-serif';

            const ctx = document.getElementById('chartPertumbuhan').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($bulan) ?>,
                    datasets: [
                        {
                            label: 'Rata-rata Berat (kg)',
                            data: <?= json_encode($berat) ?>,
                            borderColor: blueChartColor,
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            pointBackgroundColor: blueChartColor,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: false,
                            tension: 0.4, // Kurva lebih smooth
                            borderCapStyle: 'round',
                            borderJoinStyle: 'round',
                            cubicInterpolationMode: 'monotone' // Membuat kurva lebih alami
                        },
                        {
                            label: 'Rata-rata Tinggi (cm)',
                            data: <?= json_encode($tinggi) ?>,
                            borderColor: greenChartColor,
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            pointBackgroundColor: greenChartColor,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: false,
                            tension: 0.4, // Kurva lebih smooth
                            borderCapStyle: 'round',
                            borderJoinStyle: 'round',
                            cubicInterpolationMode: 'monotone' // Membuat kurva lebih alami
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Grafik Kurva Pertumbuhan Balita',
                            font: {
                                size: 18,
                                weight: 'bold',
                                family: poppinsFont
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: poppinsFont,
                                    size: 14
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            bodyFont: {
                                family: poppinsFont,
                                size: 14
                            },
                            titleFont: {
                                family: poppinsFont,
                                weight: 'bold',
                                size: 14
                            },
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.dataset.label.includes('Berat') 
                                            ? context.parsed.y + ' kg' 
                                            : context.parsed.y + ' cm';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Nilai Pengukuran',
                                font: {
                                    weight: 'bold',
                                    family: poppinsFont,
                                    size: 14
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: true
                            },
                            ticks: {
                                font: {
                                    family: poppinsFont,
                                    size: 12
                                },
                                callback: function(value) {
                                    return value;
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Periode Bulanan',
                                font: {
                                    weight: 'bold',
                                    family: poppinsFont,
                                    size: 14
                                }
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: poppinsFont,
                                    size: 12
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    elements: {
                        line: {
                            tension: 0.4 // Menentukan kelengkungan kurva
                        }
                    }
                }
            });
        </script>
        
        <footer>
            <p>Pos Pelayanan Terpadu (Posyandu) - Membangun Generasi Sehat sejak Dini | © <?= date('Y') ?> Sistem Informasi Posyandu. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
<?php 
if (isset($conn) && $conn) {
    $conn->close();
}
?>