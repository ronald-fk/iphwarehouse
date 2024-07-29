<?php
include('config.php');

if (!isset($_GET['nomor_ref']) || !isset($_GET['type'])) {
    echo json_encode([]);
    exit;
}

$nomor_ref = $_GET['nomor_ref'];
$type = $_GET['type'];

if ($type == 'incoming') {
    $sql = "SELECT i.kode_buku, p.nama_buku, i.kuantitas
            FROM incoming i
            LEFT JOIN products p ON i.kode_buku = p.kode_buku
            WHERE i.nomor_ref = ?";
} else {
    $sql = "SELECT o.kode_buku, p.nama_buku, o.kuantitas
            FROM outgoing o
            LEFT JOIN products p ON o.kode_buku = p.kode_buku
            WHERE o.nomor_invoice = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nomor_ref);
$stmt->execute();
$result = $stmt->get_result();

$details = [];
while ($row = $result->fetch_assoc()) {
    $details[] = $row;
}

echo json_encode($details);
?>
