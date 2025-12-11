<?php
include 'auth_check.php';

if (!$is_admin_or_manager) {
    header("Location: dashboard.php?message=Ошибка: у вас нет прав для выполнения этого действия.");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $role_id = $_POST['role_id'] ?? null;

    if ($user_id && $username && $email && $role_id) {
        $stmt = $conn->prepare("UPDATE acces_users SET username = ?, mail = ?, role_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $username, $email, $role_id, $user_id);
        if ($stmt->execute()) {
            header("Location: dashboard.php?message=Пользователь успешно обновлен.");
        } else {
            header("Location: dashboard.php?message=Ошибка: не удалось обновить пользователя.");
        }
    } else {
        header("Location: dashboard.php?message=Ошибка: все поля обязательны для заполнения.");
    }
} else {
    header("Location: dashboard.php");
}
?>