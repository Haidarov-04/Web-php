<?php
include 'auth_check.php';

$users_result = null;
$total_pages = 0;
$search = '';
$sort = 'id';
$order = 'desc';


$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'desc';


$count_query = "SELECT COUNT(*) as total FROM acces_users WHERE role_id = 4";
if ($search) {
    $count_query .= " AND (username LIKE ? OR mail LIKE ?)";
}
$stmt = $conn->prepare($count_query);
if ($search) {
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);


$query = "SELECT au.id, au.username, au.mail, r.role FROM acces_users au JOIN role r ON au.role_id = r.role_id WHERE au.role_id = 4";
if ($search) {
    $query .= " AND (username LIKE ? OR mail LIKE ?)";
}
$query .= " ORDER BY $sort $order LIMIT ?, ?";
$stmt = $conn->prepare($query);
if ($search) {
    $stmt->bind_param("ssii", $search_param, $search_param, $offset, $records_per_page);
} else {
    $stmt->bind_param("ii", $offset, $records_per_page);
}
$stmt->execute();
$users_result = $stmt->get_result();

$message = $_GET['message'] ?? '';
$roles = $conn->query("SELECT * FROM role");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="auth_style.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body>

<div class="admin-dashboard-main-container">
    <?php include 'topbar.php'; ?>

    <div class="content">
        <h1>Панель администратора</h1>
        <p class="text-center">Добро пожаловать, <?php echo htmlspecialchars($_SESSION['username']); ?>! (<?php echo htmlspecialchars($user_role); ?>)</p>
        <p>Это панель администратора. Здесь вы можете управлять пользователями и другими настройками сайта.</p>

        <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if (!$is_role_4): ?>
        <form action="dashboard.php" method="get">
            <input type="text" name="search" placeholder="Поиск по имени пользователя или email" value="<?php echo htmlspecialchars($search); ?>">
            <input type="submit" value="Поиск">
        </form>

        <h3>Новые зарегистрированные пользователи</h3>
        <table>
            <tr>
                <th><a href="?sort=id&order=<?php echo $sort == 'id' && $order == 'desc' ? 'asc' : 'desc'; ?>">ID</a></th>
                <th><a href="?sort=username&order=<?php echo $sort == 'username' && $order == 'desc' ? 'asc' : 'desc'; ?>">Имя пользователя</a></th>
                <th><a href="?sort=mail&order=<?php echo $sort == 'mail' && $order == 'desc' ? 'asc' : 'desc'; ?>">Email</a></th>
                <th><a href="?sort=r.role&order=<?php echo $sort == 'r.role' && $order == 'desc' ? 'asc' : 'desc'; ?>">Роль</a></th>
                <th>Действие</th>
            </tr>
            <?php while($user = $users_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['mail']); ?></td>
                <td><?php echo htmlspecialchars($user['role']); ?></td>
                <td>
                    <a href="approve_user.php?id=<?php echo $user['id']; ?>">Одобрить</a>
                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?');">Отклонять</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="<?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        
    </div>
</div>

</body>
</html>