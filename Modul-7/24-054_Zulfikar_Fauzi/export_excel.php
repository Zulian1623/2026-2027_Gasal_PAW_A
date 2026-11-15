
<?php
$koneksi = mysqli_connect("localhost", "root", "", "penjualan");

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

if ($tanggal_mulai == '' || $tanggal_selesai == '') {
    die("Rentang tanggal belum dipilih.");
}

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

// Mengatur format file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_penjualan.xls");
header("Pragma: no-cache");
header("Expires: 0");
?>

<table border="1">
    <tr>
        <th>No</th>
        <th>Total Pendapatan</th>
        <th>Tanggal Transaksi</th>
    </tr>

    <?php
    $no = 1;
    while ($row = mysqli_fetch_assoc($hasil)):
    ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $row['total_harian'] ?></td>
            <td><?= $row['tgl'] ?></td>
        </tr>
    <?php endwhile; ?>
</table>
