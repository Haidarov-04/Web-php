<?php
include 'auth_check.php';

if (!$is_admin_or_manager) {
    header("Location: dashboard.php?message=Ошибка: у вас нет прав для выполнения этого действия.");
    exit;
}

$user_id = $_GET['id'] ?? null;

if ($user_id) {
    if ($user_id == $_SESSION['user_id']) {
        header("Location: dashboard.php?message=Ошибка: вы не можете удалить свою учетную запись.");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM acces_users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        header("Location: dashboard.php?message=Пользователь успешно удален.");
    } else {
        header("Location: dashboard.php?message=Ошибка: не удалось удалить пользователя.");
    }
} else {
    header("Location: dashboard.php?message=Ошибка: не указан ID пользователя.");
}
?>