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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$name = '';
$message = '';

if ($id <= 0) {
    header("Location: contest_type.php");
    exit;
}

// Fetch the current record
$result = $conn->query("SELECT * FROM contest_type WHERE id = $id");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $name = $row['name'];
} else {
    // No record found
    header("Location: contest_type.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name']) && !empty(trim($_POST['name']))) {
        $newName = $conn->real_escape_string(trim($_POST['name']));
        
        $sql = "UPDATE contest_type SET name = '$newName' WHERE id = $id";
        
        if ($conn->query($sql)) {
            header("Location: contest_type.php");
            exit;
        } else {
            $message = "Ошибка: " . $conn->error;
            $name = $newName; // Keep the submitted value in the form
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
    <title>Редактировать тип конкурса</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="container">
        <h1>Редактировать тип конкурса</h1>

        <?php if (!empty($message)): ?>
            <p style="color: red;"><?= $message; ?></p>
        <?php endif; ?>

        <form action="contest_type_edit.php?id=<?= $id; ?>" method="post">
            <div>
                <label for="name">Название:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>" required>
            </div>
            <br>
            <div>
                <button type="submit">Сохранить</button>
            </div>
        </form>
        <br>
        <a href="contest_type.php">Назад к списку</a>
    </div>
</body>
</html>
