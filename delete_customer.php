<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : '';

if ($id) {
    $sql = "DELETE FROM customers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: customers.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
} else {
    header("Location: customers.php");
    exit;
}
?>
