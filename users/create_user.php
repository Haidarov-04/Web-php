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

$contest_type_id = isset($_GET['contest_type_id']) ? intval($_GET['contest_type_id']) : 0;
$id_contest = isset($_GET['id_contest']) ? intval($_GET['id_contest']) : 0;
$message = '';

// Step 3: Handle the final form submission to create the user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contest_id'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $contest_id = intval($_POST['contest_id']);
    
    $imageName = null;

    if (empty($first_name) || empty($last_name) || empty($email) || $contest_id <= 0) {
        $message = "Все поля обязательны для заполнения.";
    } else {
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploades/';
            $baseName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['image']['name']));
            $imageName = time() . '_' . $baseName;
            $imagePath = $uploadDir . $imageName;
    
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                $message = "Ошибка при загрузке файла.";
                $imageName = null; // Clear image name on failure
            }
        }

        if (empty($message)) {
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, image_path, contest_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $imageName, $contest_id);
            
            if ($stmt->execute()) {
                header("Location: users.php?id_contest=" . $contest_id);
                exit;
            } else {
                $message = "Ошибка при создании пользователя: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch data for dropdowns
$contest_types = $conn->query("SELECT id, name FROM contest_type ORDER BY name");

$contests = null;
if ($contest_type_id > 0) {
    $stmt = $conn->prepare("SELECT id, name FROM contest WHERE contest_type_id = ? ORDER BY name");
    $stmt->bind_param("i", $contest_type_id);
    $stmt->execute();
    $contests = $stmt->get_result();
    $stmt->close();
}

$contest_name = '';
$contest_type_name = '';
if($id_contest > 0) {
    $stmt = $conn->prepare("SELECT c.name as contest_name, ct.name as contest_type_name FROM contest c JOIN contest_type ct ON c.contest_type_id = ct.id WHERE c.id = ?");
    $stmt->bind_param("i", $id_contest);
    $stmt->execute();
    $result = $stmt->get_result();
    if($data = $result->fetch_assoc()) {
        $contest_name = $data['contest_name'];
        $contest_type_name = $data['contest_type_name'];
    }
    $stmt->close();
}


?>
<!DOCTYPE html>
<html lang="en">
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

        <?php if ($id_contest > 0): // Step 3: Final form ?>
            <h3>Конкурс: <?= htmlspecialchars($contest_type_name) ?> / <?= htmlspecialchars($contest_name) ?></h3>
            <form action="create_user.php?id_contest=<?= $id_contest; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="contest_id" value="<?= $id_contest; ?>">
                <div>
                    <label for="first_name">Имя:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <br>
                <div>
                    <label for="last_name">Фамилия:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <br>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <br>
                <div>
                    <label for="image">Изображение:</label>
                    <input type="file" id="image" name="image">
                </div>
                <br>
                <button type="submit">Сохранить</button>
            </form>
            <br>
            <a href="users.php?id_contest=<?= $id_contest; ?>" class="back-button-bottom">Назад к списку</a>

        <?php elseif ($contest_type_id > 0): // Step 2: Select Contest ?>
            <form action="create_user.php" method="GET">
                 <input type="hidden" name="contest_type_id" value="<?= $contest_type_id; ?>">
                <div>
                    <label for="contest_type_id">Тип конкурса:</label>
                    <select name="contest_type_id_disabled" onchange="this.form.submit()" disabled>
                         <?php mysqli_data_seek($contest_types, 0); while($type = $contest_types->fetch_assoc()): ?>
                            <option value="<?= $type['id']; ?>" <?= $type['id'] == $contest_type_id ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <br>
                <div>
                    <label for="id_contest">Конкурс:</label>
                    <select name="id_contest" required>
                        <option value="">-- Выберите конкурс --</option>
                        <?php if($contests): ?>
                            <?php while($contest = $contests->fetch_assoc()): ?>
                                <option value="<?= $contest['id']; ?>"><?= htmlspecialchars($contest['name']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <br>
                <button type="submit">Далее</button>
            </form>
            <br>
            <a href="create_user.php" class="back-button-bottom">Назад</a>

        <?php else: // Step 1: Select Contest Type ?>
            <form action="create_user.php" method="GET">
                <div>
                    <label for="contest_type_id">Тип конкурса:</label>
                    <select name="contest_type_id" required>
                        <option value="">-- Выберите тип конкурса --</option>
                        <?php while($type = $contest_types->fetch_assoc()): ?>
                            <option value="<?= $type['id']; ?>"><?= htmlspecialchars($type['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <br>
                <button type="submit">Далее</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>