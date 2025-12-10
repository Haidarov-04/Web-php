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

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM contest_type WHERE id=$id")) {
        header("Location: contest_type.php");
        exit;
    } else {
        echo "Ошибка удаления записи: " . $conn->error;
    }
}

$result = $conn->query("SELECT * FROM contest_type");

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Типы конкурсов</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="contest-type-main-container">
        <h1>Типы конкурсов</h1>
        <a href="contest_type_form.php">Добавить новый тип</a>
        <br><br>
        <table>
            <tr>
                <th>#</th>
                <th>Название</th>
                <th>Действия</th>
            </tr>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td>
                            <a href="contest_type_edit.php?id=<?= $row['id']; ?>">Редактировать</a> | 
                            <a href="contest_type.php?delete=<?= $row['id'] ?>" onclick="return confirm('Вы уверены, что хотите удалить этот тип конкурса?')">Удалить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">Нет данных</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
