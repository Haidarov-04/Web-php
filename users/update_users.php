<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_contest = isset($_GET['id_contest']) ? intval($_GET['id_contest']) : 0;
$message = '';

if ($id <= 0) {
    header("Location: ../contest/contest.php");
    exit;
}

$back_link = "users.php";
if ($id_contest > 0) {
    $back_link .= "?id_contest=" . $id_contest;
}

// Fetch the current user record
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: " . $back_link);
    exit;
}

// Fetch all contests and group them by contest type
$all_contests_query = "SELECT c.id, c.name, ct.name as contest_type_name 
                       FROM contest c 
                       INNER JOIN contest_type ct ON c.contest_type_id = ct.id 
                       ORDER BY ct.name, c.name";
$all_contests_result = $conn->query($all_contests_query);
$grouped_contests = [];
while ($row = $all_contests_result->fetch_assoc()) {
    $grouped_contests[$row['contest_type_name']][] = $row;
}

// Fetch the IDs of contests this user is currently in
$user_contests_result = $conn->prepare("SELECT contest_id FROM user_contests WHERE user_id = ?");
$user_contests_result->bind_param("i", $id);
$user_contests_result->execute();
$user_contests_ids_result = $user_contests_result->get_result();
$user_contests_ids = [];
while ($row = $user_contests_ids_result->fetch_assoc()) {
    $user_contests_ids[] = $row['contest_id'];
}
$user_contests_result->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $selected_contests = isset($_POST['contests']) ? (array)$_POST['contests'] : [];
    
    $imageName = $user['image_path'];

    if (empty($first_name) || empty($last_name) || empty($email) || empty($selected_contests)) {
        $message = "Имя, фамилия, email и хотя бы один конкурс обязательны для заполнения.";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploades/';
            $baseName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['image']['name']));
            $newImageName = time() . '_' . $baseName;
            $imagePath = $uploadDir . $newImageName;
    
            if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                if (!empty($user['image_path'])) {
                    $oldImagePath = $uploadDir . $user['image_path'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imageName = $newImageName;
            } else {
                $message = "Ошибка при загрузке нового изображения.";
            }
        }

        if (empty($message)) {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $first_name, $last_name, $email, $imageName, $id);
                $stmt->execute();
                $stmt->close();

                $stmt_delete = $conn->prepare("DELETE FROM user_contests WHERE user_id = ?");
                $stmt_delete->bind_param("i", $id);
                $stmt_delete->execute();
                $stmt_delete->close();

                if (!empty($selected_contests)) {
                    $stmt_insert = $conn->prepare("INSERT INTO user_contests (user_id, contest_id) VALUES (?, ?)");
                    foreach ($selected_contests as $contest_id_val) {
                        $stmt_insert->bind_param("ii", $id, $contest_id_val);
                        $stmt_insert->execute();
                    }
                    $stmt_insert->close();
                }

                $conn->commit();
                header("Location: " . $back_link);
                exit;

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $message = "Ошибка при обновлении пользователя: " . $exception->getMessage();
            }
        }
    }
    
    $user['first_name'] = $first_name;
    $user['last_name'] = $last_name;
    $user['email'] = $email;
    $user_contests_ids = $selected_contests; // Reflect changes in form if update fails
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать участника</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="container">
        <h1>Редактировать участника</h1>

        <?php if (!empty($message)): ?>
            <p style="color: red;"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" action="update_users.php?id=<?= $id ?>&id_contest=<?= $id_contest ?>">
            <div>
                <label for="first_name">Имя:</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" required>
            </div>
            <br>
            <div>
                <label for="last_name">Фамилия:</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" required>
            </div>
            <br>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
            </div>
            <br>
            <fieldset>
                <legend>Участие в конкурсах</legend>
                <?php foreach ($grouped_contests as $type_name => $contests_in_type): ?>
                    <fieldset style="margin-top: 10px; border: 1px solid #ccc; padding: 10px;">
                        <legend><?= htmlspecialchars($type_name) ?></legend>
                        <?php foreach ($contests_in_type as $contest): ?>
                            <div>
                                <input type="checkbox" name="contests[]" id="contest_<?= $contest['id'] ?>" value="<?= $contest['id'] ?>" <?= in_array($contest['id'], $user_contests_ids) ? 'checked' : '' ?>>
                                <label for="contest_<?= $contest['id'] ?>"><?= htmlspecialchars($contest['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endforeach; ?>
            </fieldset>
            <br>
            <div>
                <?php if (!empty($user['image_path'])): ?>
                    <img src="uploades/<?= htmlspecialchars($user['image_path']); ?>" width="100"><br>
                    <label>Заменить изображение:</label>
                <?php else: ?>
                    <label for="image">Изображение:</label>
                <?php endif; ?>
                <input type="file" id="image" name="image">
            </div>
            <br>
            <button type="submit">Сохранить</button>
        </form>
    </div>
    <br>
    <a href="<?= $back_link; ?>">Назад к списку</a>
</body>
</html>
