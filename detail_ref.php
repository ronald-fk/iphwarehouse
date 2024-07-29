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
    $sql = "SELECT i.tanggal, i.kode_buku, p.nama_buku, i.kuantitas, p.harga_invoice AS harga_satuan, c.name AS customer_name, c.address AS customer_address, i.keterangan
            FROM incoming i
            LEFT JOIN products p ON i.kode_buku = p.kode_buku
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE i.nomor_ref = ?";
} else {
    $sql = "SELECT o.tanggal, o.kode_buku, p.nama_buku, o.kuantitas, o.harga_satuan, c.name AS customer_name, c.address AS customer_address, o.keterangan
            FROM outgoing o
            LEFT JOIN products p ON o.kode_buku = p.kode_buku
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.nomor_invoice = ?";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("s", $nomor_ref);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
$customer_name = '';
$customer_address = '';
$keterangan = '';

while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
    $customer_name = $row['customer_name'];
    $customer_address = $row['customer_address'];
    $keterangan = $row['keterangan'];
}

usort($transactions, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});

// Menghitung total dan menambahkan ke terbilang
$total = 0;
foreach ($transactions as $transaction) {
    $total += $transaction['kuantitas'] * $transaction['harga_satuan'];
}

// Konversi angka ke terbilang
function terbilang($number) {
    $formatter = new NumberFormatter("id-ID", NumberFormatter::SPELLOUT);
    return ucwords($formatter->format($number));
}

$total_terbilang = terbilang($total) . " Rupiah";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Referensi</title>
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
            text-align: right;
            margin-top: 20px;
        }
        .terbilang-box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 20px;
        }
        .terbilang-box p {
            margin: 0;
        }
        .terbilang-amount {
            text-align: center;
            margin-top: 10px;
        }
        .rekening {
            margin-top: 20px;
            width: 50%;
            float: left;
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
                <td class="left">Nama: <?php echo htmlspecialchars($customer_name); ?></td>
                <td class="right">No. Transaksi: <?php echo htmlspecialchars($nomor_ref); ?></td>
            </tr>
            <tr>
                <td class="left">Alamat: <?php echo htmlspecialchars($customer_address); ?></td>
                <td class="right">Tanggal: <?php echo strftime('%d %B %Y'); ?></td>
            </tr>
        </table>
        <div class="separator"></div>
        <table class="transactions">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Buku</th>
                    <th>Nama Buku</th>
                    <th>Jumlah</th>
                    <th>Harga Satuan</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($transactions as $transaction) {
                    $subtotal = $transaction['kuantitas'] * $transaction['harga_satuan'];
                    echo "<tr>
                        <td>" . $no++ . "</td>
                        <td>" . htmlspecialchars($transaction['kode_buku']) . "</td>
                        <td>" . htmlspecialchars($transaction['nama_buku']) . "</td>
                        <td>" . htmlspecialchars($transaction['kuantitas']) . "</td>
                        <td>Rp " . number_format($transaction['harga_satuan'], 0, ',', '.') . ",-</td>
                        <td>Rp " . number_format($subtotal, 0, ',', '.') . ",-</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="footer-total">
            <p>Total: Rp <?php echo number_format($total, 0, ',', '.'); ?>,-</p>
        </div>

        <div class="terbilang-box">
            <p>Terbilang:</p>
            <p class="terbilang-amount"><?php echo $total_terbilang; ?></p>
        </div>

        <?php if ($keterangan) { ?>
            <div class="footer-total">
                <p>Keterangan: <?php echo htmlspecialchars($keterangan); ?></p>
            </div>
        <?php } ?>

        <div class="rekening">
            <p><strong>Penerbit Advent Indonesia Bank Account</strong></p>
            <p>Nama Bank: Mandiri cab. Bandung Cimindi</p>
            <p>Atas Nama: Yayasan Penerbit Advent Indonesia</p>
            <p>No Rekening: 132.002.1691721</p>
        </div>
        <div class="signatures">
            <div class="signature">
                <p>Hormat Kami,</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>F. Parhusip</p>
            </div>
            <div class="signature">
                <p>Penerima,</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>------------------</p>
            </div>
        </div>
        <div class="clear"></div>
    </div>
</body>
</html>
