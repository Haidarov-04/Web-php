<?php
include 'auth_check.php';
include 'mail/send_mail.php';

if (!$is_admin_or_manager) {
    header("Location: dashboard.php?message=Ошибка: у вас нет прав для выполнения этого действия.");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['id'] ?? null;
    $role_id = $_POST['role_id'] ?? null;
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($user_id && $role_id) {
        $stmt = $conn->prepare("UPDATE acces_users SET role_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $role_id, $user_id);
        if ($stmt->execute()) {
            $stmt_role = $conn->prepare("SELECT role FROM role WHERE role_id = ?");
            $stmt_role->bind_param("i", $role_id);
            $stmt_role->execute();
            $role_result = $stmt_role->get_result();
            $role_data = $role_result->fetch_assoc();
            $new_role = $role_data['role'];

            if (sendRoleChangeEmail($email, $username, $new_role)) {
                header("Location: role_4_users.php?message=Пользователь успешно одобрен, и ему отправлено уведомление.");
            } else {
                header("Location: role_4_users.php?message=Роль пользователя обновлена, но не удалось отправить уведомление.");
            }
        } else {
            header("Location: role_4_users.php?message=Ошибка: не удалось обновить роль пользователя.");
        }
    } else {
        header("Location: role_4_users.php?message=Ошибка: не все поля были заполнены.");
    }
} else {
    header("Location: dashboard.php");
}
?>