<?php
session_start();
include '../db_conn.php/db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$is_admin_or_manager = false;
if (isset($_SESSION['role_id'])) {
    $role_id_session = $_SESSION['role_id'];
    $stmt = $conn->prepare("SELECT role FROM role WHERE role_id = ?");
    $stmt->bind_param("i", $role_id_session);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($role_session = $result->fetch_assoc()) {
        if ($role_session['role'] == 'admin' || $role_session['role'] == 'руководитель') {
            $is_admin_or_manager = true;
        }
    }
}

if (!$is_admin_or_manager) {
    header("Location: dashboard.php?message=Ошибка: у вас нет прав для выполнения этого действия.");
    exit;
}

$message = '';
$error = '';
$edit_role = null;

// Handle Create and Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = $_POST['role_name'] ?? '';
    $role_id = $_POST['role_id'] ?? null;

    if (!empty($role_name)) {
        if (!empty($role_id)) {
            // Update
            $stmt = $conn->prepare("UPDATE role SET role = ? WHERE role_id = ?");
            $stmt->bind_param("si", $role_name, $role_id);
            if ($stmt->execute()) {
                $message = "Роль успешно обновлена.";
            } else {
                $error = "Ошибка обновления роли.";
            }
        } else {
            // Create
            $stmt = $conn->prepare("INSERT INTO role (role) VALUES (?)");
            $stmt->bind_param("s", $role_name);
            if ($stmt->execute()) {
                $message = "Роль успешно создана.";
            } else {
                $error = "Ошибка создания роли.";
            }
        }
    } else {
        $error = "Название роли не может быть пустым.";
    }
}


if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    if ($delete_id > 2) {
        $stmt = $conn->prepare("DELETE FROM role WHERE role_id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Роль успешно удалена.";
        } else {
            $error = "Ошибка удаления роли.";
        }
    } else {
        $error = "Эту роль нельзя удалить.";
    }
}

if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM role WHERE role_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_role = $result->fetch_assoc();
}

$roles_result = $conn->query("SELECT * FROM role ORDER BY role_id DESC");

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление ролями</title>
    <link rel="stylesheet" href="auth_style.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body>

<div class="container dashboard-container">
    <?php include 'topbar.php'; ?>

    <div class="content">
        <h1>Управление ролями</h1>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <h3><?php echo $edit_role ? 'Редактировать роль' : 'Добавить новую роль'; ?></h3>
        <form action="roles.php" method="post">
            <input type="hidden" name="role_id" value="<?php echo $edit_role['role_id'] ?? ''; ?>">
            <div class="form-group">
                <label>Название роли:</label>
                <input type="text" name="role_name" value="<?php echo htmlspecialchars($edit_role['role'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <input type="submit" value="<?php echo $edit_role ? 'Обновить роль' : 'Добавить роль'; ?>">
                <?php if ($edit_role): ?>
                    <a href="roles.php">Отменить редактирование</a>
                <?php endif; ?>
            </div>
        </form>

        <h3>Все роли</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Название роли</th>
                <th>Действие</th>
            </tr>
            <?php while($role = $roles_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $role['role_id']; ?></td>
                <td><?php echo htmlspecialchars($role['role']); ?></td>
                <td>
                    <a href="roles.php?edit_id=<?php echo $role['role_id']; ?>">Редактировать</a>
                    <?php if ($_SESSION['role_id'] == '1'): // Basic protection for first 2 roles ?>
                    | <a href="roles.php?delete_id=<?php echo $role['role_id']; ?>" onclick="return confirm('Вы уверены?');">Удалить</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

</body>
</html>
