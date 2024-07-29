<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch kode buku from the database
$sql = "SELECT kode_buku, nama_buku, harga_cost FROM products";
$result = $conn->query($sql);
$kode_buku_options = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $kode_buku_options[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nomor_ref = $_POST['nomor_ref'];
    $kode_buku = $_POST['kode_buku'];
    $kuantitas = $_POST['kuantitas'];
    $keterangan = $_POST['keterangan'];

    // Create a JSON array for buku_data
    $buku_data = [];
    for ($i = 0; $i < count($kode_buku); $i++) {
        $buku_data[] = [
            'kode_buku' => $kode_buku[$i],
            'kuantitas' => $kuantitas[$i],
            'keterangan' => $keterangan[$i]
        ];
    }
    $buku_data_json = json_encode($buku_data);

    $sql = "CALL incoming_buku_multiple(?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        exit;
    }
    $stmt->bind_param("ss", $nomor_ref, $buku_data_json);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Incoming Buku</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script>
        var booksOptions = `<?php
            foreach ($kode_buku_options as $option) {
                echo "<option value='" . $option['kode_buku'] . "' data-harga='" . $option['harga_cost'] . "'>" . $option['kode_buku'] . " - " . $option['nama_buku'] . "</option>";
            }
        ?>`;

        function addRow() {
            var table = document.getElementById("incomingTable");
            var row = table.insertRow(-1);
            row.innerHTML = `
                <td>
                    <select name="kode_buku[]" class="form-control" required onchange="updateHargaCost(this)">
                        <option value="">Pilih Buku</option>
                        ${booksOptions}
                    </select>
                </td>
                <td><input type="number" name="kuantitas[]" class="form-control" required oninput="updateSubtotal(this)"></td>
                <td><input type="number" name="harga_cost[]" class="form-control harga_cost" readonly></td>
                <td><input type="number" name="subtotal[]" class="form-control subtotal" readonly></td>
                <td><textarea name="keterangan[]" class="form-control"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="removeRow(this)">Remove</button></td>
            `;
        }

        function removeRow(button) {
            var row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
            updateGrandTotal();
        }

        function updateHargaCost(select) {
            var row = select.parentNode.parentNode;
            var harga = select.options[select.selectedIndex].getAttribute('data-harga');
            row.querySelector('.harga_cost').value = harga;
            updateSubtotal(row.querySelector('input[name="kuantitas[]"]'));
        }

        function updateSubtotal(input) {
            var row = input.parentNode.parentNode;
            var kuantitas = row.querySelector('input[name="kuantitas[]"]').value;
            var harga_cost = row.querySelector('.harga_cost').value;
            var subtotal = row.querySelector('.subtotal');
            subtotal.value = kuantitas * harga_cost;
            updateGrandTotal();
        }

        function updateGrandTotal() {
            var subtotals = document.querySelectorAll('.subtotal');
            var grandTotal = 0;
            subtotals.forEach(function(subtotal) {
                grandTotal += parseFloat(subtotal.value) || 0;
            });
            document.getElementById('grandTotal').textContent = 'Grand Total: Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Incoming Buku</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="nomor_ref">Nomor Referensi:</label>
                <input type="text" class="form-control" id="nomor_ref" name="nomor_ref" required>
            </div>
            <table class="table table-bordered" id="incomingTable">
                <thead>
                    <tr>
                        <th>Kode Buku</th>
                        <th>Kuantitas</th>
                        <th>Harga Cost</th>
                        <th>Subtotal</th>
                        <th>Keterangan</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="kode_buku[]" class="form-control" required onchange="updateHargaCost(this)">
                                <option value="">Pilih Buku</option>
                                <?php
                                foreach ($kode_buku_options as $option) {
                                    echo "<option value='" . $option['kode_buku'] . "' data-harga='" . $option['harga_cost'] . "'>" . $option['kode_buku'] . " - " . $option['nama_buku'] . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td><input type="number" name="kuantitas[]" class="form-control" required oninput="updateSubtotal(this)"></td>
                        <td><input type="number" name="harga_cost[]" class="form-control harga_cost" readonly></td>
                        <td><input type="number" name="subtotal[]" class="form-control subtotal" readonly></td>
                        <td><textarea name="keterangan[]" class="form-control"></textarea></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-primary" onclick="addRow()">Add Row</button>
            <div class="text-right mt-3">
                <p id="grandTotal">Grand Total: Rp 0,-</p>
            </div>
            <button type="submit" class="btn btn-success">Submit</button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
