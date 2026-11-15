
<?php
$koneksi = mysqli_connect("localhost", "root", "", "penjualan");

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

$labels = [];
$data_total = [];
$rekap = [];
$total_pendapatan = 0;
$total_pelanggan = 0;

if ($tanggal_mulai != '' && $tanggal_selesai != '') {

    // Mengambil rekap penjualan harian
    $query = "SELECT DATE(waktu_transaksi) AS tgl,
              SUM(total) AS total_harian
              FROM transaksi
              WHERE waktu_transaksi BETWEEN ? AND ?
              GROUP BY DATE(waktu_transaksi)
              ORDER BY tgl ASC";

    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($hasil)) {
        $rekap[] = $row;
        $labels[] = $row['tgl'];
        $data_total[] = $row['total_harian'];
        $total_pendapatan += $row['total_harian'];
    }

    // Menghitung jumlah pelanggan unik
    $query_pelanggan = "SELECT COUNT(DISTINCT pelanggan_id) AS jumlah
                        FROM transaksi
                        WHERE waktu_transaksi BETWEEN ? AND ?";

    $stmt_pelanggan = mysqli_prepare($koneksi, $query_pelanggan);
    mysqli_stmt_bind_param($stmt_pelanggan, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_pelanggan);

    $hasil_pelanggan = mysqli_stmt_get_result($stmt_pelanggan);
    $row_pelanggan = mysqli_fetch_assoc($hasil_pelanggan);

    $total_pelanggan = $row_pelanggan['jumlah'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #eee;
        }

        .card-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            padding: 20px;
            background-color: #f0f4f8;
            border-radius: 8px;
            flex: 1;
        }

        .card h3 {
            margin-top: 0;
        }

        button, .tombol {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            background-color: #2563eb;
        }

        .tombol-excel {
            background-color: #198754;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <h2>Laporan Penjualan</h2>

    <form method="GET" class="no-print">
        <label>Tanggal Mulai:</label>
        <input type="date" name="tanggal_mulai"
               value="<?= htmlspecialchars($tanggal_mulai) ?>" required>

        <label>Tanggal Selesai:</label>
        <input type="date" name="tanggal_selesai"
               value="<?= htmlspecialchars($tanggal_selesai) ?>" required>

        <button type="submit">Tampilkan</button>
    </form>

    <?php if ($tanggal_mulai != '' && $tanggal_selesai != ''): ?>

        <h3>Grafik Penjualan Harian</h3>

        <?php if (!empty($labels)): ?>
            <div style="width: 700px;">
                <canvas id="grafikPenjualan"></canvas>
            </div>

            <script>
                new Chart(document.getElementById('grafikPenjualan'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                            label: 'Total Pendapatan (Rp)',
                            data: <?= json_encode($data_total) ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgb(54, 162, 235)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Tanggal Transaksi'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Total Pendapatan (Rp)'
                                }
                            }
                        }
                    }
                });
            </script>
        <?php else: ?>
            <p>Tidak ada data transaksi pada rentang tanggal tersebut.</p>
        <?php endif; ?>

        <h3>Rekapitulasi Penjualan</h3>

        <table>
            <tr>
                <th>No</th>
                <th>Total Pendapatan</th>
                <th>Tanggal Transaksi</th>
            </tr>

            <?php $no = 1; ?>
            <?php foreach ($rekap as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>Rp <?= number_format($row['total_harian'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($row['tgl']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="card-container">
            <div class="card">
                <h3>Jumlah Pelanggan Unik</h3>
                <p><?= $total_pelanggan ?> Orang</p>
            </div>

            <div class="card">
                <h3>Total Pendapatan</h3>
                <p>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></p>
            </div>
        </div>

        <br>

        <div class="no-print">
            <button onclick="window.print()">Cetak</button>

            <a class="tombol tombol-excel"
               href="export_excel.php?tanggal_mulai=<?= urlencode($tanggal_mulai) ?>&tanggal_selesai=<?= urlencode($tanggal_selesai) ?>">
                Export Excel
            </a>
        </div>

    <?php endif; ?>

</body>
</html>
