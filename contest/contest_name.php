<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php'); 
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_GET['contest_name']) && $_GET['contest_name'] !== '') {
    $contest_name = $conn->real_escape_string($_GET['contest_name']);
    $sql_insert = "INSERT INTO `type_category` (`name`, `contest_id`) VALUES ('$contest_name', $id)";
    if ($conn->query($sql_insert)) {
        echo "<p>Добавлено: <b>$contest_name</b></p>";
    } else {
        echo "<p>Ошибка: " . $conn->error . "</p>";
    }
}

$sql_users = $id > 0
    ? "SELECT * FROM type_category WHERE contest_id = $id"
    : "SELECT * FROM type_category";

if ($result = $conn->query($sql_users)) {
    if ($result->num_rows > 0) {

    } else {

    }
} else {

}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Категория</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
    <style>
        table, td, th { border: 1px solid black; border-collapse: collapse; padding: 5px; }
    </style>
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="contest-main-container">
        <h1>Категория</h1>
        <?php
        if ($result && $result->num_rows > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Название</th><th>Все</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td><a href="../users/users.php?id_type_category=' . $row['id'] . '">Все</a></td>';
                echo '</tr>';
            }
            echo '</table>';
        } elseif ($result) {
            echo "<p>Нет данных для отображения.</p>";
        } else {
            echo "<p>Ошибка запроса: " . $conn->error . "</p>";
        }
        ?>
    </div>
    <!-- <p><a href="contest_name_form.php?id=<?php echo $id; ?>">Добавить новый тип категории</a></p>
</body>
</html>
