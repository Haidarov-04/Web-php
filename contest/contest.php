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
    if ($conn->query("DELETE FROM contest WHERE id=$id")) {
        header("Location: contest.php");
        exit;
    } else {
        echo "Ошибка удаления записи: " . $conn->error;
    }
}

$sql = "SELECT c.id, c.name, ct.name as contest_type_name 
        FROM contest c 
        JOIN contest_type ct ON c.contest_type_id = ct.id 
        ORDER BY c.id DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Конкурсы</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="contest-main-container">
        <h1>Конкурсы</h1>
        <a href="contest_form.php">Добавить новый конкурс</a>
        <br><br>
        <table>
            <tr>
                <th>#</th>
                <th>Название конкурса</th>
                <th>Тип конкурса</th>
                <th>Действия</th>
            </tr>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['contest_type_name']); ?></td>
                        <td>
                            <a href="contest_edit.php?id=<?= $row['id']; ?>">Изменить</a> |
                            <a href="../users/users.php?id_contest=<?= $row['id']; ?>">Пользователи</a> | 
                            <a href="contest.php?delete=<?= $row['id'] ?>" onclick="return confirm('Вы уверены, что хотите удалить этот конкурс?')">Удалить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Нет данных</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
