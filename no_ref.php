<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql_incoming = "SELECT MAX(i.tanggal) AS tanggal, i.nomor_ref AS nomor, 'incoming' AS type, c.name AS customer_name 
                     FROM incoming i 
                     LEFT JOIN customers c ON i.customer_id = c.id 
                     WHERE i.nomor_ref LIKE '%$search%' OR 
                           i.kode_buku LIKE '%$search%' OR 
                           i.kuantitas LIKE '%$search%' OR 
                           i.keterangan LIKE '%$search%' OR 
                           c.name LIKE '%$search%' 
                     GROUP BY i.nomor_ref";
    $sql_outgoing = "SELECT MAX(o.tanggal) AS tanggal, o.nomor_invoice AS nomor, 'outgoing' AS type, c.name AS customer_name 
                     FROM outgoing o 
                     LEFT JOIN customers c ON o.customer_id = c.id 
                     WHERE o.nomor_invoice LIKE '%$search%' OR 
                           o.kode_buku LIKE '%$search%' OR 
                           o.kuantitas LIKE '%$search%' OR 
                           o.keterangan LIKE '%$search%' OR 
                           c.name LIKE '%$search%' 
                     GROUP BY o.nomor_invoice";
} else {
    $sql_incoming = "SELECT MAX(i.tanggal) AS tanggal, i.nomor_ref AS nomor, 'incoming' AS type, c.name AS customer_name 
                     FROM incoming i 
                     LEFT JOIN customers c ON i.customer_id = c.id 
                     GROUP BY i.nomor_ref";
    $sql_outgoing = "SELECT MAX(o.tanggal) AS tanggal, o.nomor_invoice AS nomor, 'outgoing' AS type, c.name AS customer_name 
                     FROM outgoing o 
                     LEFT JOIN customers c ON o.customer_id = c.id 
                     GROUP BY o.nomor_invoice";
}

$result_incoming = $conn->query($sql_incoming);
$result_outgoing = $conn->query($sql_outgoing);

$references = [];
while ($row = $result_incoming->fetch_assoc()) {
    $references[] = $row;
}

while ($row = $result_outgoing->fetch_assoc()) {
    $references[] = $row;
}

usort($references, function($a, $b) {
    $dateComparison = strtotime($a['tanggal']) - strtotime($b['tanggal']);
    return $dateComparison == 0 ? strcmp($a['nomor'], $b['nomor']) : $dateComparison;
});

function formatTanggal($tanggal) {
    setlocale(LC_TIME, 'id_ID.utf8');
    $date = new DateTime($tanggal);
    return strftime('%d %B %Y', $date->getTimestamp());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>No. Referensi</title>
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
            max-height: 70vh; /* Set the max height of the table container */
        }
        .table th, .table td {
            vertical-align: middle;
            white-space: nowrap;
            padding: 5px; /* Reduce padding to make rows smaller */
            font-size: 12px; /* Reduce font size */
        }
        .table thead th {
            position: sticky;
            top: 0;
            background: #fff; /* Background color of the sticky header */
            z-index: 1000; /* Ensure the header is above the other content */
        }
        h1 {
            margin-top: 10px; /* Reduce the top margin of the title */
            margin-bottom: 10px; /* Reduce the bottom margin of the title */
            font-size: 20px; /* Reduce font size */
        }
        .form-inline {
            display: flex;
            justify-content: flex-end;
        }
        .form-control-search {
            width: 100px; /* Set the width of the search input */
        }
        .btn-sm-custom {
            font-size: 12px;
            padding: 2px 5px;
        }
    </style>
    <script>
        function loadDetails(nomor, type) {
            fetch(`get_details.php?nomor_ref=${nomor}&type=${type}`)
                .then(response => response.json())
                .then(data => {
                    let detailsTable = document.getElementById('detailsTableBody');
                    detailsTable.innerHTML = '';
                    data.forEach(item => {
                        let row = `<tr>
                            <td>${item.kode_buku}</td>
                            <td>${item.nama_buku}</td>
                            <td>${item.kuantitas}</td>
                        </tr>`;
                        detailsTable.innerHTML += row;
                    });
                });
        }

        function generateReport() {
            let checkboxes = document.querySelectorAll('input[name="columns[]"]:checked');
            let columns = [];
            checkboxes.forEach((checkbox) => {
                columns.push(checkbox.value);
            });

            let month = document.getElementById('reportMonth').value;
            let year = document.getElementById('reportYear').value;

            let url = `generate_report_outgoing.php?columns=${columns.join(',')}&month=${month}&year=${year}`;
            window.open(url, '_blank');
        }

        function generateIncomingReport() {
            let checkboxes = document.querySelectorAll('input[name="incoming_columns[]"]:checked');
            let columns = [];
            checkboxes.forEach((checkbox) => {
                columns.push(checkbox.value);
            });

            let month = document.getElementById('reportIncomingMonth').value;
            let year = document.getElementById('reportIncomingYear').value;

            let url = `generate_report_incoming.php?columns=${columns.join(',')}&month=${month}&year=${year}`;
            window.open(url, '_blank');
        }
    </script>
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
                    <a class="nav-link" href="no_ref.php">No. Ref</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customers.php">Customer List</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Log Out</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid container-custom mt-1">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">No. Referensi</h1>
            <form method="GET" action="" class="form-inline mb-0">
                <input type="text" name="search" class="form-control form-control-search mr-sm-2" placeholder="Search No Referensi" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary btn-sm-custom">Search</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>No Referensi</th>
                        <th>Type</th>
                        <th>Nama Customer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($references) > 0) {
                        $no = 1;
                        foreach ($references as $row) {
                            echo "<tr>
                                <td>" . $no++ . "</td>
                                <td>" . htmlspecialchars(formatTanggal($row['tanggal'])) . "</td>
                                <td>" . htmlspecialchars($row['nomor']) . "</td>
                                <td>" . htmlspecialchars($row['type']) . "</td>
                                <td>" . htmlspecialchars($row['customer_name']) . "</td>
                                <td>";
                            if ($row['type'] == 'incoming') {
                                echo "<a href='detail_ref_incoming.php?nomor_ref=" . urlencode($row['nomor']) . "&type=" . $row['type'] . "' class='btn btn-info btn-sm btn-sm-custom' target='_blank'>PDF</a>";
                            } else {
                                echo "<a href='detail_ref.php?nomor_ref=" . urlencode($row['nomor']) . "&type=" . $row['type'] . "' class='btn btn-info btn-sm btn-sm-custom' target='_blank'>PDF</a>";
                            }
                            echo " <button class='btn btn-primary btn-sm btn-sm-custom' data-toggle='modal' data-target='#detailModal' onclick='loadDetails(\"" . $row['nomor'] . "\", \"" . $row['type'] . "\")'>Lihat</button>";
                            echo "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>No references found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-success btn-sm-custom" data-toggle="modal" data-target="#reportModal">Report Outgoing</button>
        <button type="button" class="btn btn-success btn-sm-custom" data-toggle="modal" data-target="#reportIncomingModal">Report Incoming</button>
        <a href="index.php" class="btn btn-secondary">Kembali</a>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Referensi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Kode Buku</th>
                                <th>Nama Buku</th>
                                <th>Kuantitas</th>
                            </tr>
                        </thead>
                        <tbody id="detailsTableBody">
                            <!-- Dynamic content will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">Select Columns for Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="reportForm" method="POST" action="generate_report_outgoing.php" target="_blank">
                        <input type="hidden" name="report_type" value="outgoing">
                        <div class="form-group">
                            <label for="reportMonth">Month</label>
                            <select id="reportMonth" name="month" class="form-control">
                                <option value="">All</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reportYear">Year</label>
                            <input type="number" id="reportYear" name="year" class="form-control" value="<?php echo date('Y'); ?>">
                        </div>
                        <div class="checkbox-container">
                            <label><input type="checkbox" name="columns[]" value="tanggal" checked> Tanggal</label>
                            <label><input type="checkbox" name="columns[]" value="nomor_invoice" checked> No Referensi</label>
                            <label><input type="checkbox" name="columns[]" value="kode_buku" checked> Kode Buku</label>
                            <label><input type="checkbox" name="columns[]" value="nama_buku" checked> Nama Buku</label>
                            <label><input type="checkbox" name="columns[]" value="customer_name" checked> Nama Customer</label>
                            <label><input type="checkbox" name="columns[]" value="kuantitas" checked> Jumlah</label>
                            <label><input type="checkbox" name="columns[]" value="harga_satuan" checked> Harga Satuan</label>
                            <label><input type="checkbox" name="columns[]" value="subtotal" checked> Subtotal</label>
                        </div>
                        <button type="button" class="btn btn-success btn-sm-custom mt-3" onclick="generateReport()">Generate Report</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Incoming Modal -->
    <div class="modal fade" id="reportIncomingModal" tabindex="-1" aria-labelledby="reportIncomingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportIncomingModalLabel">Select Columns for Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="reportIncomingForm" method="POST" action="generate_report_incoming.php" target="_blank">
                        <input type="hidden" name="report_type" value="incoming">
                        <div class="form-group">
                            <label for="reportIncomingMonth">Month</label>
                            <select id="reportIncomingMonth" name="month" class="form-control">
                                <option value="">All</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reportIncomingYear">Year</label>
                            <input type="number" id="reportIncomingYear" name="year" class="form-control" value="<?php echo date('Y'); ?>">
                        </div>
                        <div class="checkbox-container">
                            <label><input type="checkbox" name="incoming_columns[]" value="tanggal" checked> Tanggal</label>
                            <label><input type="checkbox" name="incoming_columns[]" value="nomor_ref" checked> No Referensi</label>
                            <label><input type="checkbox" name="incoming_columns[]" value="kode_buku" checked> Kode Buku</label>
                            <label><input type="checkbox" name="incoming_columns[]" value="no_job" checked> No Job</label>
                            <label><input type="checkbox" name="incoming_columns[]" value="nama_buku" checked> Nama Buku</label>
                            <label><input type="checkbox" name="incoming_columns[]" value="kuantitas" checked> Jumlah</label>
                            <label><input type="checkbox" name="incoming_columns[]" value="harga_cost" checked> Harga Cost</label>
                            <label><input type="checkbox" name="incoming_columns[]" value="subtotal" checked> Subtotal</label>
                        </div>
                        <button type="button" class="btn btn-success btn-sm-custom mt-3" onclick="generateIncomingReport()">Generate Report</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
