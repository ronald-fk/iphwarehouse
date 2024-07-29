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
    $sql = "SELECT * FROM customers WHERE 
            name LIKE '%$search%' OR
            contact_number LIKE '%$search%' OR
            address LIKE '%$search%'
            ORDER BY name";
} else {
    $sql = "SELECT * FROM customers ORDER BY name";
}

$result = $conn->query($sql);

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; // Check if the user is admin
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer List</title>
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
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .form-inline {
            display: flex;
            justify-content: flex-end;
        }
        .form-control-search {
            width: 200px;
        }
    </style>
    <script>
        function confirmDelete() {
            return confirm("Apakah anda yakin?");
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

    <div class="container-fluid container-custom mt-5">
        <div class="header-flex">
            <h1 class="mb-0">Customer List</h1>
            <form method="GET" action="" class="form-inline">
                <input type="text" name="search" class="form-control form-control-search mr-sm-2" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
            </form>
        </div>
        <?php if ($is_admin): ?>
            <a href="add_customer.php" class="btn btn-success mb-3">Add Customer</a>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>No</th>
                        <th>Nama Customer</th>
                        <th>No Kontak</th>
                        <th>Alamat</th>
                        <?php if ($is_admin): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        $no = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>" . $no++ . "</td>
                                <td>" . htmlspecialchars($row['name']) . "</td>
                                <td>" . htmlspecialchars($row['contact_number']) . "</td>
                                <td>" . htmlspecialchars($row['address']) . "</td>";
                            if ($is_admin) {
                                echo "<td>
                                    <a href='edit_customer.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm'>Edit</a>
                                    <a href='delete_customer.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick='return confirmDelete()'>Delete</a>
                                </td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='" . ($is_admin ? 5 : 4) . "' class='text-center'>No customers found</td></tr>";
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
