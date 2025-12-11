<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php'); 
    exit;
}

// Role check
$is_admin_or_manager = isset($_SESSION['role_id']) && ($_SESSION['role_id'] == '1' || $_SESSION['role_id'] == '3');
if (!$is_admin_or_manager) {
    header("Location: contest_type.php?message=Ошибка: у вас нет прав для выполнения этого действия.");
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
            $message = "Ошибка: " . $conn->error;
        }
    } else {
        $message = "Название типа конкурса не может быть пустым.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
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