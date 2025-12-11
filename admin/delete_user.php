<?php
session_start();
include '../db_conn.php/db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


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