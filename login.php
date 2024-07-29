<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .login-form {
            width: 300px;
            margin: auto;
            margin-top: 100px;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 5px;
        }
        .login-form h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .login-form label {
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php
session_start();
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, password, role FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role']; // Pastikan role disimpan di sesi
            header("Location: index.php");
            exit;
        } else {
            $login_error = "Invalid password.";
        }
    } else {
        $login_error = "No user found.";
    }
}
?>



<div class="login-form">
    <h2>Login</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
        <?php if (isset($login_error)) : ?>
            <div class="alert alert-danger mt-3" role="alert">
                <?= $login_error ?>
            </div>
        <?php endif; ?>
    </form>
    <div class="mt-3 text-center">
        <a href="register.php">Daftar</a>
    </div>
</div>

<!-- Bootstrap JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

