<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';

// If the user is not logged in redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php'); // Redirect to admin login
    exit;
}

$name = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name']) && !empty(trim($_POST['name']))) {
        $name = $conn->real_escape_string(trim($_POST['name']));
        
        $sql = "INSERT INTO contest_type (name) VALUES ('$name')";
        
        if ($conn->query($sql)) {
            header("Location: contest_type.php");
            exit;
        } else {
            $message = "Error: " . $conn->error;
        }
    } else {
        $message = "Название типа конкурса не может быть пустым.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Добавить тип конкурса</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="container">
        <h1>Добавить новый тип конкурса</h1>

        <?php if (!empty($message)): ?>
            <p style="color: red;"><?= $message; ?></p>
        <?php endif; ?>

        <form action="contest_type_form.php" method="post">
            <div>
                <label for="name">Название:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>" required>
            </div>
            <br>
            <div>
                <button type="submit">Добавить</button>
            </div>
        </form>
        <br>
        <a href="contest_type.php">Назад к списку</a>
    </div>
</body>
</html>