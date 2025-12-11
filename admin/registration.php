<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
 session_start();
include_once __DIR__ . '/../db_conn.php/db.php';
// include 'auth_check.php';
include 'mail/send_mail.php';
$is_admin = false;


if (isset($_SESSION['role_id'])) {
    if ($_SESSION['role_id'] == 1) {
        $is_admin = true;
    }
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $password = random_int(100000, 999999);
    if ($is_admin) {
        $role_id = $_POST['role_id'] ?? 2;
    } else {
        $role_id = 4;
    }


    if ($username && $email && $password) {

        // Проверка на существующего пользователя
        $stmt = $conn->prepare("SELECT * FROM acces_users WHERE username = ? OR mail = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Имя пользователя или email уже существует!";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Добавляем verify_code в базу
            $stmt = $conn->prepare(
                "INSERT INTO acces_users (username, mail, role_id, pass) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssis", $username, $email, $role_id, $hash);

            if ($stmt->execute()) {

                // Отправка кода на Gmail
                if (sendVerificationEmail($email, $password, $username)) {
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
    <?php if ($is_admin): ?>
    <?php include '../admin/topbar.php'; ?>
    <?php endif; ?>
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

        <?php if ($is_admin): ?>
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
        <?php endif; ?>

        <div class="form-group">
            <input type="submit" value="Зарегистрироваться">
        </div>
                    
    </form>
    <?php 
    if (!isset($_SESSION['user_id'])) {
    
    echo '<div class="form-group">
            <p>Уже зарегистрированы? <a href="login.php">Войти</a></p>
          </div>';
    }
    ?>
</div>

</body>
</html>
