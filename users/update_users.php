<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';

// If the user is not logged in redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php'); // Redirect to admin login
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_contest = isset($_GET['id_contest']) ? intval($_GET['id_contest']) : 0;
$message = '';

// Only the user ID is essential to fetch the user.
if ($id <= 0) {
    header("Location: ../contest/contest.php"); // Or a general error page
    exit;
}

// Prepare the redirect URL for later use (back link and post-update redirect)
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    
    $imageName = $user['image_path']; // Keep old image by default

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $message = "Имя, фамилия и email обязательны для заполнения.";
    } else {
        // Handle image upload if a new one is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploades/';
            $baseName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['image']['name']));
            $newImageName = time() . '_' . $baseName;
            $imagePath = $uploadDir . $newImageName;
    
            if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                // Delete the old image if it exists
                if (!empty($user['image_path'])) {
                    $oldImagePath = $uploadDir . $user['image_path'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imageName = $newImageName; // Set new image name for DB update
            } else {
                $message = "Ошибка при загрузке нового изображения.";
            }
        }

        if (empty($message)) {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, image_path = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $imageName, $id);
            
            if ($stmt->execute()) {
                header("Location: " . $back_link);
                exit;
            } else {
                $message = "Ошибка при обновлении пользователя: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Update user array with new data to display in form if update fails
    $user['first_name'] = $first_name;
    $user['last_name'] = $last_name;
    $user['email'] = $email;
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
