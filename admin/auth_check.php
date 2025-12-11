<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../db_conn.php/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$is_admin = false;
$is_manager = false;
$is_admin_or_manager = false;
$is_role_4 = false;
$user_role = '';

if (isset($_SESSION['role_id'])) {
    $role_id = $_SESSION['role_id'];

    if ($role_id == 4) {
        $is_role_4 = true;
    }

    // Кеширование роли в сессии
    if (isset($_SESSION['user_role'])) {
        $user_role = $_SESSION['user_role'];
    } else {
        $stmt = $conn->prepare("SELECT role FROM role WHERE role_id = ?");
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($role_data = $result->fetch_assoc()) {
            $user_role = $role_data['role'];
            $_SESSION['user_role'] = $user_role; // Сохраняем роль в сессии
        }
        $stmt->close();
    }
    
    if ($user_role == 'admin') {
        $is_admin = true;
    }
    if ($user_role == 'руководитель') {
        $is_manager = true;
    }
    if ($is_admin || $is_manager) {
        $is_admin_or_manager = true;
    }
}
?>