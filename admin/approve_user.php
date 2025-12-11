<?php
include 'auth_check.php';

if (!$is_admin_or_manager) {
    echo "У вас нет прав для просмотра этой страницы.";
    exit;
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: dashboard.php?message=Ошибка: не указан ID пользователя.");
    exit;
}

$stmt = $conn->prepare("SELECT id, username, mail, role_id FROM acces_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$roles_result = $conn->query("SELECT * FROM role WHERE role_id != 4");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Одобрить пользователя</title>
    <link rel="stylesheet" href="auth_style.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body>

<?php include 'topbar.php'; ?>

<div class="container">
    <h2>Одобрить пользователя</h2>

    <form action="update_user_role.php" method="post">
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['mail']); ?>">

        <div class="form-group">
            <label for="username">Имя пользователя:</label>
            <input type="text" id="username" name="username_disabled" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email_disabled" value="<?php echo htmlspecialchars($user['mail']); ?>" disabled>
        </div>

        <div class="form-group">
            <label for="role_id">Новая роль:</label>
            <select id="role_id" name="role_id">
                <?php while($role = $roles_result->fetch_assoc()): ?>
                    <option value="<?php echo $role['role_id']; ?>" <?php echo ($user['role_id'] == $role['role_id']) ? 'selected' : ''; ?> >
                        <?php echo $role['role']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <input type="submit" value="Утвердить и отправить уведомление">
        </div>
    </form>
</div>

</body>
</html>
