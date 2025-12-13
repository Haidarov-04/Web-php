<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';

// If the user is not logged in redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    header("Location: users.php?message=Ошибка: не указан ID пользователя.");
    exit;
}

// Fetch the user's details
$stmt_user = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$stmt_user->close();

if (!$user) {
    header("Location: users.php?message=Ошибка: пользователь не найден.");
    exit;
}

// Fetch all contests for the given user, including the contest type
$stmt_contests = $conn->prepare(
    "SELECT c.id, c.name, ct.name as contest_type_name
     FROM contest c
     INNER JOIN user_contests uc ON c.id = uc.contest_id
     INNER JOIN contest_type ct ON c.contest_type_id = ct.id
     WHERE uc.user_id = ?
     ORDER BY ct.name, c.name"
);
$stmt_contests->bind_param("i", $user_id);
$stmt_contests->execute();
$contests_result = $stmt_contests->get_result();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Конкурсы пользователя</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="container">
        <h1>Конкурсы пользователя: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>

        <?php if ($contests_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>#</th>
                    <th>Тип конкурса</th>
                    <th>Название конкурса</th>
                </tr>
                <?php $i = 1; while($contest = $contests_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($contest['contest_type_name']); ?></td>
                        <td><?= htmlspecialchars($contest['name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Этот пользователь не участвует ни в одном конкурсе.</p>
        <?php endif; ?>
        
        <br>
        <a href="users.php" class="back-button-bottom">Назад к списку пользователей</a>
    </div>
</body>
</html>
