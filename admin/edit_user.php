<?php
session_start();
include '../db_conn.php/db.php';

// If the user is not logged in redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if the user is an admin
$is_admin = false;
if (isset($_SESSION['role_id'])) {
    $role_id = $_SESSION['role_id'];
    $stmt = $conn->prepare("SELECT role FROM role WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($role = $result->fetch_assoc()) {
        if ($role['role'] == 'admin') {
            $is_admin = true;
        }
    }
}

if (!$is_admin) {
    echo "У вас нет прав для просмотра этой страницы.";
    exit;
}

// Get user data
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

// Get roles
$roles_result = $conn->query("SELECT * FROM role");

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать пользователя</title>
    <link rel="stylesheet" href="auth_style.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body>

<?php include 'topbar.php'; ?>

<div class="container">
    <h2>Редактировать пользователя</h2>

    <form action="update_user.php" method="post">
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

        <div class="form-group">
            <label for="username">Имя пользователя:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['mail']); ?>" required>
        </div>

        <div class="form-group">
            <label for="role_id">Роль:</label>
            <select id="role_id" name="role_id">
                <?php while($role = $roles_result->fetch_assoc()): ?>
                    <option value="<?php echo $role['role_id']; ?>" <?php echo ($user['role_id'] == $role['role_id']) ? 'selected' : ''; ?> >
                        <?php echo $role['role']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <input type="submit" value="Обновить пользователя">
        </div>
    </form>
</div>

</body>
</html>