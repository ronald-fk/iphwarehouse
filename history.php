<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$kode_buku = isset($_GET['kode_buku']) ? $_GET['kode_buku'] : '';
$no_referensi = isset($_GET['no_referensi']) ? $_GET['no_referensi'] : '';

$sql_incoming = "SELECT tanggal, nomor_ref AS nomor, keterangan, kuantitas FROM incoming WHERE kode_buku = ?";
$sql_outgoing = "SELECT tanggal, nomor_invoice AS nomor, keterangan, kuantitas FROM outgoing WHERE kode_buku = ?";

if ($no_referensi) {
    $sql_incoming .= " AND nomor_ref LIKE ?";
    $sql_outgoing .= " AND nomor_invoice LIKE ?";
}

$stmt_incoming = $conn->prepare($sql_incoming);
$stmt_outgoing = $conn->prepare($sql_outgoing);

if ($no_referensi) {
    $param = "%$no_referensi%";
    $stmt_incoming->bind_param("ss", $kode_buku, $param);
    $stmt_outgoing->bind_param("ss", $kode_buku, $param);
} else {
    $stmt_incoming->bind_param("s", $kode_buku);
    $stmt_outgoing->bind_param("s", $kode_buku);
}

$stmt_incoming->execute();
$result_incoming = $stmt_incoming->get_result();

$stmt_outgoing->execute();
$result_outgoing = $stmt_outgoing->get_result();

$history = [];
while ($row = $result_incoming->fetch_assoc()) {
    $row['type'] = 'incoming';
    $history[] = $row;
}

while ($row = $result_outgoing->fetch_assoc()) {
    $row['type'] = 'outgoing';
    $history[] = $row;
}

usort($history, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});

$stok_akhir = 0;

function formatTanggal($tanggal) {
    setlocale(LC_TIME, 'id_ID.utf8');
    $date = new DateTime($tanggal);
    return strftime('%d %B %Y', $date->getTimestamp());
}

function getHargaCost($kode_buku, $conn) {
    $sql = "SELECT harga_cost FROM products WHERE kode_buku = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kode_buku);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['harga_cost'];
}

function getNoJob($kode_buku, $conn) {
    $sql = "SELECT no_job FROM products WHERE kode_buku = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kode_buku);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['no_job'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>History Buku</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand img {
            height: 30px;
            margin-right: 10px;
        }
        .container-custom {
            padding-left: 100px;
            padding-right: 100px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table th, .table td {
            vertical-align: middle;
            white-space: nowrap;
            padding: 5px;
        }
        .total-values {
            margin-top: 20px;
            font-weight: bold;
        }
        .history-section {
            margin-top: 40px;
        }
        .form-inline input {
            width: 300px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">
            <img src="iph_logo.png" alt="Logo">
            Penerbit Advent Indonesia
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Product List</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="create.php">Add New Product</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="incoming.php">Incoming Buku</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="keluar.php">Keluar Buku</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="laporan.php">Laporan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="no_ref.php">No. Ref</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Log Out</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid container-custom mt-5">
        <h1 class="mb-4">History Buku: <?= htmlspecialchars($kode_buku) ?></h1>
        <form method="GET" action="" class="form-inline mb-3">
            <input type="hidden" name="kode_buku" value="<?php echo htmlspecialchars($kode_buku); ?>">
            <input type="text" name="no_referensi" class="form-control mr-sm-2" placeholder="Search by No Referensi" value="<?php echo htmlspecialchars($no_referensi); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Tanggal</th>
                        <th>No Referensi</th>
                        <th>No. Job</th>
                        <th>Keterangan</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                        <th>Stok Akhir</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($history as $entry) {
                        if ($entry['type'] == 'incoming') {
                            $stok_akhir += $entry['kuantitas'];
                            $masuk = $entry['kuantitas'];
                            $keluar = 0;
                        } else {
                            $stok_akhir -= $entry['kuantitas'];
                            $masuk = 0;
                            $keluar = $entry['kuantitas'];
                        }
                        $balance = $stok_akhir * getHargaCost($kode_buku, $conn);  // Fungsi getHargaCost untuk mendapatkan harga cost buku
                        $no_job = getNoJob($kode_buku, $conn); // Fungsi getNoJob untuk mendapatkan no_job
                        echo "<tr>
                            <td>" . htmlspecialchars(formatTanggal($entry['tanggal'])) . "</td>
                            <td>" . htmlspecialchars($entry['nomor']) . "</td>
                            <td>" . htmlspecialchars($no_job) . "</td>
                            <td>" . htmlspecialchars($entry['keterangan']) . "</td>
                            <td>" . $masuk . "</td>
                            <td>" . $keluar . "</td>
                            <td>" . $stok_akhir . "</td>
                            <td>Rp " . number_format($balance, 0, ',', '.') . ",-</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <a href="index.php" class="btn btn-secondary">Kembali</a>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
