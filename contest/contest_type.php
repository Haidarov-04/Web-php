<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php');
    exit;
}


$is_admin_or_manager = isset($_SESSION['role_id']) && ($_SESSION['role_id'] == '1' || $_SESSION['role_id'] == '3');

if ($is_admin_or_manager && isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM contest_type WHERE id=$id")) {
        header("Location: contest_type.php");
        exit;
    } else {
        echo "Ошибка удаления записи: " . $conn->error;
    }
}

$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$count_query = "SELECT COUNT(*) as total FROM contest_type";
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$sql = "SELECT * FROM contest_type LIMIT $records_per_page OFFSET $offset";
$result = $conn->query($sql);

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
        <?php if ($is_admin_or_manager): ?>
            <a href="contest_type_form.php">Добавить новый тип</a>
        <?php endif; ?>
        <br><br>
        <table>
            <tr>
                <th>#</th>
                <th>Название</th>
                <?php if ($is_admin_or_manager): ?>
                    <th>Действия</th>
                <?php endif; ?>
            </tr>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <?php if ($is_admin_or_manager): ?>
                            <td>
                                <a href="contest_type_edit.php?id=<?= $row['id']; ?>">Редактировать</a> | 
                                <a href="contest_type.php?delete=<?= $row['id'] ?>" onclick="return confirm('Вы уверены, что хотите удалить этот тип конкурса?')">Удалить</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">Нет данных</td>
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
