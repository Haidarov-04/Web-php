<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../db_conn.php/db.php'; 

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";
    $role_id = $_POST['role_id'] ?? 2; 

    if ($username && $email && $password) {

        $stmt = $conn->prepare("SELECT * FROM acces_users WHERE username = ? OR mail = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Имя пользователя или email уже существует!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO acces_users (username, mail, pass, role_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $username, $email, $hash, $role_id);

            if ($stmt->execute()) {
                $success = "Регистрация прошла успешно!";
            } else {
                $error = "Ошибка: " . $stmt->error;
            }
        }
    } else {
        $error = "Все поля обязательны для заполнения!";
    }
}

$roles = $conn->query("SELECT * FROM role");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="auth_style.css">
</head>
<body>

<div class="container">
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
            <label>Пароль:</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Роль:</label>
            <select name="role_id">
                <?php while($role = $roles->fetch_assoc()): ?>
                    <option value="<?= $role['role_id'] ?>"><?php echo $role['role']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <input type="submit" value="Зарегистрироваться">
        </div>
    </form>

    <div class="text-center">
        <a href="login.php">Уже есть аккаунт? Войдите здесь</a>
    </div>
</div>

</body>
</html>