<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php'); 
    exit;
}

if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4) {
    header('Location: ../admin/dashboard.php');
    exit;
}


$is_admin_or_manager = isset($_SESSION['role_id']) && ($_SESSION['role_id'] == '1' || $_SESSION['role_id'] == '3');

if ($is_admin_or_manager && isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM contest WHERE id=$id")) {
        header("Location: contest.php");
        exit;
    } else {
        echo "Ошибка удаления записи: " . $conn->error;
    }
}

$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$count_query = "SELECT COUNT(*) as total FROM contest";
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$sql = "SELECT c.id, c.name, ct.name as contest_type_name 
        FROM contest c 
        JOIN contest_type ct ON c.contest_type_id = ct.id 
        ORDER BY c.id DESC
        LIMIT $records_per_page OFFSET $offset";
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
        <?php if ($is_admin_or_manager): ?>
            <a href="contest_form.php">Добавить новый конкурс</a>
        <?php endif; ?>
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
                            <?php if ($is_admin_or_manager): ?>
                                <a href="contest_edit.php?id=<?= $row['id']; ?>">Изменить</a> |
                            <?php endif; ?>
                            <a href="../users/users.php?id_contest=<?= $row['id']; ?>">Пользователи</a>
                            <?php if ($is_admin_or_manager): ?>
                                | <a href="contest.php?delete=<?= $row['id'] ?>" onclick="return confirm('Вы уверены, что хотите удалить этот конкурс?')">Удалить</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Нет данных</td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>
