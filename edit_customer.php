<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];

    $sql = "UPDATE customers SET name = ?, contact_number = ?, address = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $contact_number, $address, $id);

    if ($stmt->execute()) {
        header("Location: customers.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}

$sql = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$customer = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Customer</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Edit Customer</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Nama Customer</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($customer['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="contact_number">No Kontak</label>
                <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?= htmlspecialchars($customer['contact_number']) ?>" required>
            </div>
            <div class="form-group">
                <label for="address">Alamat</label>
                <textarea class="form-control" id="address" name="address" required><?= htmlspecialchars($customer['address']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="customers.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
