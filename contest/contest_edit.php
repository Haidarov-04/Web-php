<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php'); 
    exit;
}


$is_admin_or_manager = isset($_SESSION['role_id']) && ($_SESSION['role_id'] == '1' || $_SESSION['role_id'] == '3');
if (!$is_admin_or_manager) {
    header("Location: contest.php?message=Ошибка: у вас нет прав для выполнения этого действия.");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$name = '';
$contest_type_id = 0;
$message = '';

if ($id <= 0) {
    header("Location: contest.php");
    exit;
}


$contest_types_result = $conn->query("SELECT * FROM contest_type ORDER BY name ASC");


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare("SELECT name, contest_type_id FROM contest WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $contest = $result->fetch_assoc();
        $name = $contest['name'];
        $contest_type_id = $contest['contest_type_id'];
    } else {
        header("Location: contest.php");
        exit;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $contest_type_id = isset($_POST['contest_type_id']) ? intval($_POST['contest_type_id']) : 0;

    if (!empty($name) && $contest_type_id > 0) {
        $escaped_name = $conn->real_escape_string($name);
        
        $stmt = $conn->prepare("UPDATE contest SET name = ?, contest_type_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $escaped_name, $contest_type_id, $id);
        
        if ($stmt->execute()) {
            header("Location: contest.php");
            exit;
        } else {
            $message = "Ошибка: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Название и тип конкурса обязательны.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать конкурс</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="container">
        <h1>Редактировать конкурс</h1>

        <?php if (!empty($message)): ?>
            <p style="color: red;"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form action="contest_edit.php?id=<?= $id; ?>" method="post">
            <div>
                <label for="name">Название конкурса:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>" required>
            </div>
            <br>
            <div>
                <label for="contest_type_id">Тип конкурса:</label>
                <select id="contest_type_id" name="contest_type_id" required>
                    <option value="">-- Выберите тип --</option>
                    <?php mysqli_data_seek($contest_types_result, 0); ?>
                    <?php while($type = $contest_types_result->fetch_assoc()): ?>
                        <option value="<?= $type['id']; ?>" <?= ($contest_type_id == $type['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <br>
            <div>
                <button type="submit">Сохранить</button>
            </div>
        </form>
        <br>
        <a href="contest.php">Назад к списку</a>
    </div>
</body>
</html>
