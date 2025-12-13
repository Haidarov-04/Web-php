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
    header("Location: ../admin/dashboard.php?message=Ошибка: у вас нет прав для выполнения этого действия.");
    exit;
}

$message = '';
$first_name = '';
$last_name = '';
$email = '';
$selected_contests = [];

// Pre-select the contest if coming from a specific contest page
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['id_contest'])) {
    $preselected_id = intval($_GET['id_contest']);
    if ($preselected_id > 0) {
        $selected_contests[] = $preselected_id;
    }
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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $selected_contests = isset($_POST['contests']) ? (array)$_POST['contests'] : [];
    
    $imageName = null;

    if (empty($first_name) || empty($last_name) || empty($email) || empty($selected_contests)) {
        $message = "Имя, фамилия, email и хотя бы один конкурс обязательны для заполнения.";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploades/';
            $baseName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['image']['name']));
            $imageName = time() . '_' . $baseName;
            $imagePath = $uploadDir . $imageName;
    
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                $message = "Ошибка при загрузке файла.";
                $imageName = null;
            }
        }

        if (empty($message)) {
            $conn->begin_transaction();
            try {
                // Step 1: Insert user
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, image_path) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $first_name, $last_name, $email, $imageName);
                $stmt->execute();

                // Step 2: Get the new user's ID
                $user_id = $conn->insert_id;

                // Step 3: Insert into the junction table for each selected contest
                if (!empty($selected_contests)) {
                    $stmt_contest = $conn->prepare("INSERT INTO user_contests (user_id, contest_id) VALUES (?, ?)");
                    foreach ($selected_contests as $contest_id) {
                        $stmt_contest->bind_param("ii", $user_id, $contest_id);
                        $stmt_contest->execute();
                    }
                    $stmt_contest->close();
                }

                $conn->commit();
                
                $redirect_url = "users.php";
                if (!empty($_POST['origin_contest_id'])) {
                    $redirect_url .= "?id_contest=" . intval($_POST['origin_contest_id']);
                }
                header("Location: " . $redirect_url);
                exit;

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $message = "Ошибка при создании пользователя: " . $exception->getMessage();
            } finally {
                if (isset($stmt)) $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить участника</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="container">
        <h1>Добавить участника</h1>

        <?php if (!empty($message)): ?>
            <p style="color: red;"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form action="create_user.php?id_contest=<?= htmlspecialchars($_GET['id_contest'] ?? '') ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="origin_contest_id" value="<?= htmlspecialchars($_GET['id_contest'] ?? '') ?>">
            <div>
                <label for="first_name">Имя:</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name) ?>" required>
            </div>
            <br>
            <div>
                <label for="last_name">Фамилия:</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name) ?>" required>
            </div>
            <br>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <br>
            
            <fieldset>
                <legend>Выберите конкурсы</legend>
                <?php foreach ($grouped_contests as $type_name => $contests_in_type): ?>
                    <fieldset style="margin-top: 10px; border: 1px solid #ccc; padding: 10px;">
                        <legend><?= htmlspecialchars($type_name) ?></legend>
                        <?php foreach ($contests_in_type as $contest): ?>
                            <div>
                                <input type="checkbox" name="contests[]" id="contest_<?= $contest['id'] ?>" value="<?= $contest['id'] ?>" <?= in_array($contest['id'], $selected_contests) ? 'checked' : '' ?>>
                                <label for="contest_<?= $contest['id'] ?>"><?= htmlspecialchars($contest['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endforeach; ?>
            </fieldset>
            
            <br>
            <div>
                <label for="image">Изображение:</label>
                <input type="file" id="image" name="image">
            </div>
            <br>
            <button type="submit">Сохранить</button>
        </form>
        <br>
        <a href="users.php" class="back-button-bottom">Назад к списку</a>
    </div>
</body>
</html>