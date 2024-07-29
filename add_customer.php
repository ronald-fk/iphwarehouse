<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];

    $sql = "INSERT INTO customers (name, contact_number, address) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $contact_number, $address);

    if ($stmt->execute()) {
        header("Location: customers.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Customer</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Add Customer</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Nama Customer</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="contact_number">No Kontak</label>
                <input type="text" class="form-control" id="contact_number" name="contact_number" required>
            </div>
            <div class="form-group">
                <label for="address">Alamat</label>
                <textarea class="form-control" id="address" name="address" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Add Customer</button>
            <a href="customers.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
