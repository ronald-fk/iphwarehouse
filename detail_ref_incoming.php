<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['nomor_ref']) || !isset($_GET['type'])) {
    header("Location: no_ref.php");
    exit;
}

$nomor_ref = $_GET['nomor_ref'];
$type = $_GET['type'];

if ($type == 'incoming') {
    $sql = "SELECT i.tanggal, i.kode_buku, p.no_job, p.nama_buku, i.kuantitas, p.harga_cost
            FROM incoming i
            LEFT JOIN products p ON i.kode_buku = p.kode_buku
            WHERE i.nomor_ref = ?";
} else {
    header("Location: no_ref.php");
    exit;
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("s", $nomor_ref);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

usort($transactions, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});

// Menghitung total
$total = 0;
foreach ($transactions as $transaction) {
    $total += $transaction['kuantitas'] * $transaction['harga_cost'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Referensi Incoming</title>
    <style>
        .container {
            width: 800px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 50px;
            float: left;
        }
        .header h2, .header p {
            margin: 0;
        }
        .header div {
            display: inline-block;
            vertical-align: top;
        }
        .header:after {
            content: "";
            display: table;
            clear: both;
        }
        .separator {
            border-bottom: 2px solid #000;
            margin: 20px 0;
        }
        .details {
            margin-bottom: 20px;
            width: 100%;
        }
        .details td {
            padding: 5px;
        }
        .details td.left {
            text-align: left;
        }
        .details td.right {
            text-align: right;
        }
        .transactions th, .transactions td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .transactions {
            border-collapse: collapse;
            width: 100%;
        }
        .footer-total {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .signatures {
            width: 100%;
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .signature {
            text-align: center;
            width: auto;
            margin-left: 100px;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="iph_logo.png" alt="Logo">
            <div>
                <h2>YAYASAN PENERBIT ADVENT INDONESIA</h2>
                <p>Jl. Raya Cimindi No.72 Bandung 40184</p>
                <p>Telp. (022) 6030392</p>
            </div>
        </div>
        <div class="separator"></div>
        <table class="details">
            <tr>
                <td class="left">No. Referensi: <?php echo htmlspecialchars($nomor_ref); ?></td>
                <td class="right">Tanggal: <?php echo strftime('%d %B %Y'); ?></td>
            </tr>
        </table>
        <div class="separator"></div>
        <table class="transactions">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Buku</th>
                    <th>No Job</th>
                    <th>Nama Buku</th>
                    <th>Jumlah</th>
                    <th>Harga Cost</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($transactions as $transaction) {
                    $subtotal = $transaction['kuantitas'] * $transaction['harga_cost'];
                    echo "<tr>
                        <td>" . $no++ . "</td>
                        <td>" . htmlspecialchars($transaction['kode_buku']) . "</td>
                        <td>" . htmlspecialchars($transaction['no_job']) . "</td>
                        <td>" . htmlspecialchars($transaction['nama_buku']) . "</td>
                        <td>" . htmlspecialchars($transaction['kuantitas']) . "</td>
                        <td>Rp " . number_format($transaction['harga_cost'], 0, ',', '.') . ",-</td>
                        <td>Rp " . number_format($subtotal, 0, ',', '.') . ",-</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="footer-total">
            <p>Total: Rp <?php echo number_format($total, 0, ',', '.'); ?>,-</p>
        </div>

        <div class="signatures">
            <div class="signature">
                <p>Yang Menyerahkan,</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>E. Susanto</p>
            </div>
            <div class="signature">
                <p>Penerima,</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>R. Karwur</p>
            </div>
        </div>
        <div class="clear"></div>
    </div>
</body>
</html>
