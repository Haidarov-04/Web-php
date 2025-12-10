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

$id_contest = null; // Initialize id_contest to null
if (isset($_GET['id_contest'])) {
   $id_contest = intval($_GET['id_contest']);
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // First, get the image path to delete the file
    $user_query = $conn->query("SELECT image_path FROM users WHERE id=$id");
    if ($user_query && $user_query->num_rows > 0) {
        $user = $user_query->fetch_assoc();
        if (!empty($user['image_path'])) {
            $image_file_to_delete = 'uploades/' . $user['image_path'];
            if (file_exists($image_file_to_delete)) {
                unlink($image_file_to_delete);
            }
        }
    }

    // Now, delete the user record
    if ($conn->query("DELETE FROM users WHERE id=$id")){
        echo "deleted";
    }else{
        echo "error", $conn->error();
    }
    // Correct redirection to include id_contest if it exists
    $redirect_url = "users.php";
    if ($id_contest !== null) {
        $redirect_url .= "?id_contest=" . $id_contest;
    }
    header("Location: " . $redirect_url);
    exit;
}

$contest_heading = "участники"; // Default heading
if ($id_contest !== null) {
    $contest_query = $conn->prepare("SELECT name FROM contest WHERE id = ?");
    $contest_query->bind_param("i", $id_contest);
    $contest_query->execute();
    $contest_result = $contest_query->get_result();
    if ($contest_result && $contest_result->num_rows > 0) {
        $contest_data = $contest_result->fetch_assoc();
        $contest_heading = "Участники конкурса: " . htmlspecialchars($contest_data['name']);
    }
    $contest_query->close();
}


$users_request = "SELECT u.id as id, u.first_name, u.last_name, u.email, u.image_path,
                        c_t.name as contest_type,  c.name as contest
                        FROM users u, contest_type c_t, contest c
                        WHERE u.contest_id = c.id 
                        and c_t.id = c.contest_type_id";

if ($id_contest !== null) { // Use the initialized $id_contest
    $users_request .= " AND u.contest_id = $id_contest";
}
                        
if($result = $conn->query($users_request)){

}else{
    echo "error";
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRUD Users</title>
    <link rel="stylesheet" href="../admin/auth_style.css">
    <link rel="stylesheet" href="../admin/topbar.css">
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="users-main-container">
        <h1><?= $contest_heading; ?></h1>
        <a href="create_user.php?id_contest=<?= $id_contest; ?>"> Добавить</a>
        <br><br>
        <table>
            <tr>
                <th>#</th>
                <th>Имя</th>
                <th>Фамилия</th>
                <th>Email</th>
                <th>Image</th>
                <th>Конкурс</th>
                <th>Номинация</th>
                <th>Действия</th>
            </tr>
            <?php $i = 1; while($row = $result->fetch_assoc()){ ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <!-- <td><?= $row['id']; ?></td> -->
                    <td><?= $row['first_name']; ?></td>
                    <td><?= $row['last_name']; ?></td>
                    <td><?= $row['email']; ?></td>
                    <td>
                        <?php if (!empty($row['image_path'])): ?>
                            <img src="uploades/<?= $row['image_path']; ?>" width="50" height="50" alt="User Image">
                        <?php else: ?>
                            Нет рисунка
                        <?php endif; ?>
                    </td>
                    <td><?= $row['contest']; ?></td>
                    <td><?= $row['contest_type']; ?></td>
                    <td>
                        <a href="update_users.php?id=<?= $row['id']; ?>&id_contest=<?= $id_contest; ?>">Изменить</a> | 
                        <a href="users.php?delete=<?= $row['id'] ?>&id_contest=<?= $id_contest; ?>" onclick="return confirm('Удалить?')">Удалить</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>
