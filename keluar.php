<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nomor_invoice = $_POST['nomor_invoice'];
    $kode_buku = $_POST['kode_buku'];
    $kuantitas = $_POST['kuantitas'];
    $keterangan = $_POST['keterangan'];
    $harga_satuan = $_POST['harga_satuan'];
    $customer = $_POST['customer'];
    $other_customer = $_POST['other_customer'];

    // Validate customer selection
    if ($customer == "" && $other_customer == "") {
        echo "<div class='alert alert-danger' role='alert'>Please select a customer or enter a customer name.</div>";
    } else {
        // Use the other customer name if "Lainnya" is selected
        if ($customer == "other") {
            $customer = $other_customer;
        }

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

        $sql = "CALL keluar_buku(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nomor_invoice, $buku_data_json);

        if ($stmt->execute()) {
            echo "Buku berhasil dikeluarkan.";
            header("Location: index.php");
            exit;
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Mengambil data customer dan buku dari database
$sql_customers = "SELECT id, name FROM customers";
$result_customers = $conn->query($sql_customers);

$sql_books = "SELECT kode_buku, nama_buku, harga_invoice FROM products";
$result_books = $conn->query($sql_books);

$books_options = '';
while ($row = $result_books->fetch_assoc()) {
    $books_options .= "<option value='" . $row['kode_buku'] . "' data-harga='" . $row['harga_invoice'] . "'>" . $row['kode_buku'] . " - " . $row['nama_buku'] . "</option>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Outgoing Buku</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script>
        var booksOptions = `<?php echo $books_options; ?>`;

        function addRow() {
            var table = document.getElementById("outgoingTable");
            var row = table.insertRow(-1);
            row.innerHTML = `
                <td>
                    <select name="kode_buku[]" class="form-control" required onchange="updateHargaSatuan(this)">
                        <option value="">Pilih Buku</option>
                        ${booksOptions}
                    </select>
                </td>
                <td><input type="number" name="kuantitas[]" class="form-control" required oninput="updateSubtotal(this)"></td>
                <td><input type="text" name="harga_satuan[]" class="form-control harga_satuan" required oninput="updateSubtotal(this)" onblur="formatInput(this)"></td>
                <td><input type="text" name="subtotal[]" class="form-control subtotal" readonly></td>
                <td><textarea name="keterangan[]" class="form-control"></textarea></td>
                <td><button type="button" class="btn btn-danger" onclick="removeRow(this)">Remove</button></td>
            `;
        }

        function removeRow(button) {
            var row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
            updateGrandTotal();
        }

        function updateHargaSatuan(select) {
            var row = select.parentNode.parentNode;
            var harga = select.options[select.selectedIndex].getAttribute('data-harga');
            row.querySelector('.harga_satuan').value = formatRupiah(harga);
            updateSubtotal(row.querySelector('input[name="kuantitas[]"]'));
        }

        function updateSubtotal(input) {
            var row = input.parentNode.parentNode;
            var kuantitas = row.querySelector('input[name="kuantitas[]"]').value;
            var harga_satuan = parseFloat(row.querySelector('input[name="harga_satuan[]"]').value.replace(/[^\d.-]/g, ''));
            var subtotal = row.querySelector('.subtotal');
            var total = kuantitas * harga_satuan;
            subtotal.value = formatRupiah(total);
            updateGrandTotal();
        }

        function updateGrandTotal() {
            var subtotals = document.querySelectorAll('.subtotal');
            var grandTotal = 0;
            subtotals.forEach(function(subtotal) {
                grandTotal += parseFloat(subtotal.value.replace(/[^\d.-]/g, '')) || 0;
            });
            document.getElementById('grandTotal').textContent = 'Grand Total: Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal) + ',-';
        }

        function formatRupiah(angka) {
            var number_string = angka.toString().replace(/[^\d.-]/g, ''),
                split = number_string.split('.'),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return 'Rp ' + rupiah + ',-';
        }

        function formatInput(input) {
            var value = parseFloat(input.value.replace(/[^\d.-]/g, ''));
            input.value = formatRupiah(value);
        }

        function updateCustomerDetails(select) {
            var otherCustomerField = document.getElementById('other_customer');
            if (select.value === 'other') {
                otherCustomerField.style.display = 'block';
                otherCustomerField.required = true;
            } else {
                otherCustomerField.style.display = 'none';
                otherCustomerField.required = false;
            }
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Outgoing Buku</h1>
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="nomor_invoice">Nomor Invoice:</label>
                    <input type="text" class="form-control" id="nomor_invoice" name="nomor_invoice" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="customer">Customer:</label>
                    <select id="customer" name="customer" class="form-control" required onchange="updateCustomerDetails(this)">
                        <option value="">Pilih Customer</option>
                        <?php
                        while($row = $result_customers->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                        }
                        ?>
                        <option value="other">Lainnya</option>
                    </select>
                    <input type="text" class="form-control mt-2" id="other_customer" name="other_customer" placeholder="Nama Customer" style="display:none;">
                </div>
            </div>
            <table class="table table-bordered" id="outgoingTable">
                <thead>
                    <tr>
                        <th>Kode Buku</th>
                        <th>Kuantitas</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                        <th>Keterangan</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="kode_buku[]" class="form-control" required onchange="updateHargaSatuan(this)">
                                <option value="">Pilih Buku</option>
                                <?php echo $books_options; ?>
                            </select>
                        </td>
                        <td><input type="number" name="kuantitas[]" class="form-control" required oninput="updateSubtotal(this)"></td>
                        <td><input type="text" name="harga_satuan[]" class="form-control harga_satuan" required oninput="updateSubtotal(this)" onblur="formatInput(this)"></td>
                        <td><input type="text" name="subtotal[]" class="form-control subtotal" readonly></td>
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
