<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
include('config.php');

$columns = isset($_POST['columns']) ? $_POST['columns'] : [];
$search = isset($_POST['search']) ? $_POST['search'] : '';
$user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

if (empty($columns)) {
    echo "No columns selected";
    exit;
}

$sql = "SELECT * FROM products";
if (!empty($search)) {
    $sql .= " WHERE 
            kode_buku LIKE '%$search%' OR
            nama_buku LIKE '%$search%' OR
            keterangan LIKE '%$search%' OR
            harga_cost LIKE '%$search%' OR
            harga_invoice LIKE '%$search%' OR
            kuantitas LIKE '%$search%' OR
            nilai_inventori LIKE '%$search%'";
}

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "No data found";
    exit;
}

$mpdf = new \Mpdf\Mpdf();

// Gaya CSS untuk mengatur font dan ukuran font
$stylesheet = "
    body {
        font-family: 'Calibri', sans-serif;
        font-size: 12px; /* Ubah ukuran font sesuai keinginan */
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid black;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
";

$html = '<h1>Product List</h1>';
$html .= '<table>';
$html .= '<thead>';
$html .= '<tr>';

if (in_array('kode_buku', $columns)) $html .= '<th>Kode Buku</th>';
if (in_array('nama_buku', $columns)) $html .= '<th>Nama Buku</th>';
if (in_array('keterangan', $columns)) $html .= '<th>Keterangan</th>';
if (in_array('harga_cost', $columns)) $html .= '<th>Harga Cost</th>';
if (in_array('harga_invoice', $columns)) $html .= '<th>Harga Invoice</th>';
if (in_array('harga_umum', $columns)) $html .= '<th>Harga Umum</th>';
if (in_array('harga_anggota', $columns)) $html .= '<th>Harga Anggota</th>';
if (in_array('kuantitas', $columns)) $html .= '<th>Kuantitas</th>';
if (in_array('nilai_inventori', $columns)) $html .= '<th>Nilai Inventori</th>';

$html .= '</tr>';
$html .= '</thead>';
$html .= '<tbody>';

$grand_total = 0; // Initialize grand total
$total_slow_moving = 0; // Initialize total slow moving

while ($row = $result->fetch_assoc()) {
    $harga_invoice = $row['harga_invoice'];
    $harga_umum = $harga_invoice / 0.3;
    $harga_anggota = $harga_invoice + ($harga_invoice / 0.3 * 0.06) + ($harga_invoice / 0.3 * 0.14) + ($harga_invoice / 0.3 * 0.5 * 0.1);
    $nilai_inventori = $row['kuantitas'] * $row['harga_cost'];
    $grand_total += $nilai_inventori; // Add to grand total

    if (strtolower($row['keterangan']) == 'slow moving') {
        $total_slow_moving += $nilai_inventori; // Add to total slow moving if condition met
    }

    $html .= '<tr>';
    if (in_array('kode_buku', $columns)) $html .= '<td>' . $row['kode_buku'] . '</td>';
    if (in_array('nama_buku', $columns)) $html .= '<td>' . $row['nama_buku'] . '</td>';
    if (in_array('keterangan', $columns)) $html .= '<td>' . $row['keterangan'] . '</td>';
    if (in_array('harga_cost', $columns)) $html .= '<td>Rp ' . number_format($row['harga_cost'], 0, ',', '.') . ',-</td>';
    if (in_array('harga_invoice', $columns)) $html .= '<td>Rp ' . number_format($row['harga_invoice'], 0, ',', '.') . ',-</td>';
    if (in_array('harga_umum', $columns)) $html .= '<td>Rp ' . number_format($harga_umum, 0, ',', '.') . ',-</td>';
    if (in_array('harga_anggota', $columns)) $html .= '<td>Rp ' . number_format($harga_anggota, 0, ',', '.') . ',-</td>';
    if (in_array('kuantitas', $columns)) $html .= '<td>' . $row['kuantitas'] . '</td>';
    if (in_array('nilai_inventori', $columns)) $html .= '<td>Rp ' . number_format($nilai_inventori, 0, ',', '.') . ',-</td>';
    $html .= '</tr>';
}

$html .= '</tbody>';
$html .= '</table>';

$html .= '<p><strong>Grand Total: Rp ' . number_format($grand_total, 0, ',', '.') . ',-</strong></p>';
if ($total_slow_moving > 0) {
    $html .= '<p><strong>Total Slow Moving: Rp ' . number_format($total_slow_moving, 0, ',', '.') . ',-</strong></p>';
}

// // Tambahkan nama user di kanan bawah
// $html .= '<p style="text-align: right;">Generated by: ' . $user . '</p>';

// Memasukkan gaya CSS ke mPDF
$mpdf->WriteHTML($stylesheet, 1);
$mpdf->WriteHTML($html);
$mpdf->Output();
?>
