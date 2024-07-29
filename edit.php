<?php
include('config.php');

if (isset($_GET['kode_buku'])) {
    $kode_buku = $_GET['kode_buku'];

    $sql = "SELECT * FROM products WHERE kode_buku='$kode_buku'";
    $result = $conn->query($sql);
    $product = $result->fetch_assoc();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $new_kode_buku = $_POST['kode_buku'];
        $nama_buku = $_POST['nama_buku'];
        $keterangan = isset($_POST['keterangan']) ? $_POST['keterangan'] : '';
        $harga_cost = $_POST['harga_cost'];
        $harga_invoice = $_POST['harga_invoice'];
        $kuantitas = $_POST['kuantitas'];
        $no_job = $_POST['no_job'];
        $nilai_inventori = $kuantitas * $harga_cost;

        // Validate kode_buku format
        if (!preg_match('/^\d{3}[A-Z]{2,3}-\d{2}[A-Z\d]?$/', $new_kode_buku)) {
            echo "<div class='alert alert-danger' role='alert'>Invalid Kode Buku format. Expected format: 162JOB-01, 162JOB-001, 162JOB-01A, 162SU-01, 162SU-001, or 162SU-01A</div>";
        } else {
            $sql = "UPDATE products SET kode_buku='$new_kode_buku', nama_buku='$nama_buku', keterangan='$keterangan', harga_cost='$harga_cost', harga_invoice='$harga_invoice', kuantitas='$kuantitas', no_job='$no_job', nilai_inventori='$nilai_inventori' WHERE kode_buku='$kode_buku'";
            if ($conn->query($sql) === TRUE) {
                header("Location: index.php");
                exit;
            } else {
                echo "Error updating record: " . $conn->error;
            }
        }
    }
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .container {
            max-width: 500px;
            margin-top: 50px;
            border: 1px solid #ccc;
            padding: 30px;
            border-radius: 5px;
        }
        .container h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group label {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Edit Product</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="kode_buku">Kode Buku:</label>
            <input type="text" class="form-control" id="kode_buku" name="kode_buku" value="<?= htmlspecialchars($product['kode_buku']) ?>" required>
        </div>
        <div class="form-group">
            <label for="nama_buku">Nama Buku:</label>
            <input type="text" class="form-control" id="nama_buku" name="nama_buku" value="<?= htmlspecialchars($product['nama_buku']) ?>" required>
        </div>
        <div class="form-group">
            <label for="keterangan">Keterangan:</label>
            <input type="text" class="form-control" id="keterangan" name="keterangan" value="<?= htmlspecialchars($product['keterangan']) ?>">
        </div>
        <div class="form-group">
            <label for="harga_cost">Harga Cost:</label>
            <input type="number" step="0.01" class="form-control" id="harga_cost" name="harga_cost" value="<?= htmlspecialchars($product['harga_cost']) ?>" required>
        </div>
        <div class="form-group">
            <label for="harga_invoice">Harga Invoice:</label>
            <input type="number" step="0.01" class="form-control" id="harga_invoice" name="harga_invoice" value="<?= htmlspecialchars($product['harga_invoice']) ?>" required>
        </div>
        <div class="form-group">
            <label for="kuantitas">Kuantitas:</label>
            <input type="number" class="form-control" id="kuantitas" name="kuantitas" value="<?= htmlspecialchars($product['kuantitas']) ?>" required>
        </div>
        <div class="form-group">
            <label for="no_job">No Job:</label>
            <input type="number" class="form-control" id="no_job" name="no_job" value="<?= htmlspecialchars($product['no_job']) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Update Product</button>
        <a href="index.php" class="btn btn-secondary">Kembali</a>
    </form>
</div>

<!-- Bootstrap JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
