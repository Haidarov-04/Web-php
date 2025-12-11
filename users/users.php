<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db_conn.php/db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php'); 
    exit;
}

if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$id_contest = null; 
if (isset($_GET['id_contest'])) {
   $id_contest = intval($_GET['id_contest']);
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    
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

    
    if ($conn->query("DELETE FROM users WHERE id=$id")){
        echo "deleted";
    }else{
        echo "error", $conn->error();
    }
    
    $redirect_url = "users.php";
    if ($id_contest !== null) {
        $redirect_url .= "?id_contest=" . $id_contest;
    }
    header("Location: " . $redirect_url);
    exit;
}

$contest_heading = "участники"; 
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

if ($id_contest !== null) { 
    $users_request .= " AND u.contest_id = $id_contest";
}

$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$count_query = "SELECT COUNT(*) as total FROM users";
if ($id_contest !== null) {
    $count_query .= " WHERE contest_id = $id_contest";
}
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$users_request .= " LIMIT $records_per_page OFFSET $offset";
                        
if($result = $conn->query($users_request)){

}else{
    echo "error";
};
?>
<?php
$is_admin_or_manager = isset($_SESSION['role_id']) && ($_SESSION['role_id'] == '1' || $_SESSION['role_id'] == '3');
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
        <?php if ($is_admin_or_manager): ?>
        <a href="create_user.php?id_contest=<?= $id_contest; ?>"> Добавить</a>
        <?php endif; ?>
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
                <?php if ($is_admin_or_manager): ?>
                <th>Действия</th>
                <?php endif; ?>
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
                    <?php if ($is_admin_or_manager): ?>
                    <td>
                        <a href="update_users.php?id=<?= $row['id']; ?>&id_contest=<?= $id_contest; ?>">Изменить</a> | 
                        <a href="users.php?delete=<?= $row['id'] ?>&id_contest=<?= $id_contest; ?>" onclick="return confirm('Удалить?')">Удалить</a>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php } ?>
        </table>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php if ($id_contest !== null) echo '&id_contest=' . $id_contest; ?>" class="<?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>
