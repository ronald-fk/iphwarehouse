<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user'; // Default ke 'user' jika tidak ada sesi

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql = "SELECT * FROM products WHERE 
            kode_buku LIKE '%$search%' OR
            nama_buku LIKE '%$search%' OR
            keterangan LIKE '%$search%' OR
            harga_cost LIKE '%$search%' OR
            harga_invoice LIKE '%$search%' OR
            kuantitas LIKE '%$search%' OR
            nilai_inventori LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM products";
}

$result = $conn->query($sql);

$total_inventory_value = 0;
$total_slow_moving_inventory_value = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product List</title>
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
        .total-values {
            margin-top: 10px; /* Reduce the top margin */
            font-weight: bold;
            font-size: 12px; /* Reduce font size */
            display: flex;
            justify-content: space-between; /* Align elements to the sides */
            align-items: center; /* Center align the button vertically */
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
        .modal-content {
            font-size: 13px;
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
                <?php if ($role == 'admin') : ?>
                <li class="nav-item">
                    <a class="nav-link" href="create.php">Add New Product</a>
                </li>
                <?php endif; ?>
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
            <h1 class="mb-0">Product List</h1>
            <form method="GET" action="" class="form-inline mb-0">
                <input type="text" name="search" class="form-control form-control-search mr-sm-2" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary btn-sm-custom">Search</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>No</th>
                        <th>Kode Buku</th>
                        <th>Nama Buku</th>
                        <th>Keterangan</th>
                        <th>Harga Cost</th>
                        <th>Harga Invoice</th>
                        <th>Harga Umum</th>
                        <th>Harga Anggota</th>
                        <th>Kuantitas</th>
                        <th>Nilai Inventori</th>
                        <th>Actions</th>
                        <?php if ($role == 'admin') : ?>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        $no = 1;
                        while($row = $result->fetch_assoc()) {
                            $harga_invoice = $row['harga_invoice'];
                            $harga_umum = $harga_invoice / 0.3;
                            $harga_anggota = $harga_invoice + ($harga_invoice / 0.3 * 0.06) + ($harga_invoice / 0.3 * 0.14) + ($harga_invoice / 0.3 * 0.5 * 0.1);
                            $nilai_inventori = $row['kuantitas'] * $row['harga_cost'];
                            $total_inventory_value += $nilai_inventori;
                            
                            if (strtolower($row['keterangan']) == 'slow moving') {
                                $total_slow_moving_inventory_value += $nilai_inventori;
                            }

                            echo "<tr>
                                <td>" . $no++ . "</td>
                                <td>" . $row['kode_buku'] . "</td>
                                <td>" . $row['nama_buku'] . "</td>
                                <td>" . $row['keterangan'] . "</td>
                                <td>Rp " . number_format($row['harga_cost'], 0, ',', '.') . ",-</td>
                                <td>Rp " . number_format($row['harga_invoice'], 0, ',', '.') . ",-</td>
                                <td>Rp " . number_format($harga_umum, 0, ',', '.') . ",-</td>
                                <td>Rp " . number_format($harga_anggota, 0, ',', '.') . ",-</td>
                                <td>" . $row['kuantitas'] . "</td>
                                <td>Rp " . number_format($nilai_inventori, 0, ',', '.') . ",-</td>";
                                
                            if ($role == 'admin') {
                                echo "<td>
                                    <a href='edit.php?kode_buku=" . $row['kode_buku'] . "' class='btn btn-warning btn-sm btn-sm-custom'>Edit</a>
                                    <a href='delete.php?kode_buku=" . $row['kode_buku'] . "' class='btn btn-danger btn-sm btn-sm-custom' onclick='return confirm(\"Apakah anda yakin?\")'>Delete</a>
                                    <a href='history.php?kode_buku=" . $row['kode_buku'] . "' class='btn btn-info btn-sm btn-sm-custom'>History</a>
                                </td>";
                            } else {
                                echo "<td>
                                    <a href='history.php?kode_buku=" . $row['kode_buku'] . "' class='btn btn-info btn-sm btn-sm-custom'>History</a>
                                </td>";
                            }

                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='11' class='text-center'>No products found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="total-values">
            <div>
                <p>Total Nilai Inventori: Rp <?php echo number_format($total_inventory_value, 0, ',', '.'); ?>,-</p>
                <p>Total Nilai Inventori "Slow Moving": Rp <?php echo number_format($total_slow_moving_inventory_value, 0, ',', '.'); ?>,-</p>
            </div>
            <!-- Convert to PDF Button -->
            <button type="button" class="btn btn-success btn-sm-custom" data-toggle="modal" data-target="#pdfModal">Convert to PDF</button>
        </div>
        
        <!-- Modal -->
        <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pdfModalLabel">Select Columns to Convert</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="pdfForm" method="POST" action="convert_to_pdf.php" target="_blank">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <div class="checkbox-container">
                                <label><input type="checkbox" name="columns[]" value="kode_buku" checked> Kode Buku</label>
                                <label><input type="checkbox" name="columns[]" value="nama_buku" checked> Nama Buku</label>
                                <label><input type="checkbox" name="columns[]" value="keterangan" checked> Keterangan</label>
                                <label><input type="checkbox" name="columns[]" value="harga_cost" checked> Harga Cost</label>
                                <label><input type="checkbox" name="columns[]" value="harga_invoice" checked> Harga Invoice</label>
                                <label><input type="checkbox" name="columns[]" value="harga_umum" checked> Harga Umum</label>
                                <label><input type="checkbox" name="columns[]" value="harga_anggota" checked> Harga Anggota</label>
                                <label><input type="checkbox" name="columns[]" value="kuantitas" checked> Kuantitas</label>
                                <label><input type="checkbox" name="columns[]" value="nilai_inventori" checked> Nilai Inventori</label>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm-custom mt-3">Convert to PDF</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
