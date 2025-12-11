<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include '../db_conn.php/db.php';
include 'mail/send_mail.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $password = "12345";  // фиксированный пароль
    $role_id = $_POST['role_id'] ?? 2;

    if ($username && $email && $password) {

        // Проверка на существующего пользователя
        $stmt = $conn->prepare("SELECT * FROM acces_users WHERE username = ? OR mail = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Имя пользователя или email уже существует!";
        } else {

            // Генерация 6-значного кода
            $verify_code = random_int(100000, 999999);

            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Добавляем verify_code в базу
            $stmt = $conn->prepare(
                "INSERT INTO acces_users (username, mail, role_id, pass) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssis", $username, $email, $role_id, $hash);

            if ($stmt->execute()) {

                // Отправка кода на Gmail
                if (sendVerificationEmail($email, $verify_code, $username)) {
                    $success = "Регистрация успешна! Код подтверждения отправлен на email.";
                } else {
                    $error = "Пользователь создан, но email не отправлен!";
                }

            } else {
                $error = "Ошибка: " . $stmt->error;
            }
        }
    } else {
        $error = "Все поля обязательны!";
    }
}

// список ролей
$roles = $conn->query("SELECT * FROM role");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>

<div class="container">
    <?php include '../admin/topbar.php'; ?>
    <h2>Регистрация</h2>

    <?php if ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-group">
            <label>Имя пользователя:</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Роль:</label>
            <select name="role_id">
                <?php while ($role = $roles->fetch_assoc()): ?>
                    <option value="<?= $role['role_id'] ?>">
                        <?= $role['role'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <input type="submit" value="Зарегистрироваться">
        </div>
    </form>
</div>

</body>
</html>
