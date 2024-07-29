<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['kode_buku'])) {
    $kode_buku = $_GET['kode_buku'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete related entries in incoming table
        $conn->query("DELETE FROM incoming WHERE kode_buku = '$kode_buku'");
        
        // Delete related entries in outgoing table
        $conn->query("DELETE FROM outgoing WHERE kode_buku = '$kode_buku'");
        
        // Delete the product
        $conn->query("DELETE FROM products WHERE kode_buku = '$kode_buku'");
        
        // Commit transaction
        $conn->commit();
        echo "Product successfully deleted.";
    } catch (mysqli_sql_exception $exception) {
        // Rollback transaction in case of error
        $conn->rollback();
        echo "Error: " . $exception->getMessage();
    }

    header("Location: index.php");
    exit;
} else {
    echo "Error: Kode buku tidak ditemukan.";
}
?>
