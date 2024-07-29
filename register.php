<?php
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user'; // Set default role to 'user'

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);

    if ($stmt->execute()) {
        echo "Registration successful. You can now <a href='login.php'>login</a>.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .register-form {
            width: 400px;
            margin: auto;
            margin-top: 100px;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .register-form h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .register-form label {
            font-weight: bold;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="register-form">
    <h2>Register</h2>
    <?php if (isset($success_message)) : ?>
        <div class="alert alert-success" role="alert">
            <?= $success_message ?>
        </div>
    <?php elseif (isset($error_message)) : ?>
        <div class="alert alert-danger" role="alert">
            <?= $error_message ?>
        </div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Register</button>
    </form>
    <div class="mt-3 text-center">
        <a href="login.php">Already have an account? Login here</a>
    </div>
</div>

<!-- Bootstrap JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
