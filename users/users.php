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
if (isset($_GET['id_contest']) && intval($_GET['id_contest']) > 0) {
   $id_contest = intval($_GET['id_contest']);
}

$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
$is_error = isset($_GET['error']);

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_message = '';
    $delete_error = false;

    // First, get the image path to delete the file
    $user_query = $conn->prepare("SELECT image_path FROM users WHERE id = ?");
    $user_query->bind_param("i", $id);
    $user_query->execute();
    $result = $user_query->get_result();
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (!empty($user['image_path'])) {
            $image_file_to_delete = 'uploades/' . $user['image_path'];
            if (file_exists($image_file_to_delete)) {
                unlink($image_file_to_delete);
            }
        }
    }
    $user_query->close();

    // Then, delete the user from the database using a prepared statement
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $delete_message = "Участник успешно удален.";
    } else {
        $delete_message = "Ошибка при удалении участника: " . $stmt->error;
        $delete_error = true;
    }
    $stmt->close();
    
    // Finally, redirect back to the user list with a status message
    $redirect_url = "users.php";
    $query_params = [];
    if ($id_contest !== null) {
        $query_params['id_contest'] = $id_contest;
    }
    $query_params['message'] = urlencode($delete_message);
    if ($delete_error) {
        $query_params['error'] = '1';
    }
    header("Location: " . $redirect_url . '?' . http_build_query($query_params));
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


$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$params = [];
$types = '';

$is_all_users_view = ($id_contest === null);

if ($is_all_users_view) {
    // Logic for showing all unique users
    $count_query = "SELECT COUNT(*) as total FROM users";
    $users_request = "SELECT u.id, u.first_name, u.last_name, u.email, u.image_path, COUNT(uc.contest_id) as contest_count
                      FROM users u
                      LEFT JOIN user_contests uc ON u.id = uc.user_id
                      GROUP BY u.id, u.first_name, u.last_name, u.email, u.image_path
                      ORDER BY u.last_name, u.first_name";

    $stmt_count = $conn->prepare($count_query);

} else {
    // Logic for showing users in a specific contest
    $count_query = "SELECT COUNT(u.id) as total
                    FROM users u
                    INNER JOIN user_contests uc ON u.id = uc.user_id
                    WHERE uc.contest_id = ?";
    
    $users_request = "SELECT u.id, u.first_name, u.last_name, u.email, u.image_path,
                        c.name as contest, c.id as contest_id,
                        ct.name as contest_type
                      FROM users u
                      INNER JOIN user_contests uc ON u.id = uc.user_id
                      INNER JOIN contest c ON uc.contest_id = c.id
                      INNER JOIN contest_type ct ON c.contest_type_id = ct.id
                      WHERE uc.contest_id = ?
                      ORDER BY u.last_name, u.first_name";

    $params[] = $id_contest;
    $types .= 'i';
    $stmt_count = $conn->prepare($count_query);
    $stmt_count->bind_param($types, ...$params);
}

// Get total records for pagination
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$stmt_count->close();

// Get user records for the current page
$users_request .= " LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $records_per_page;
$params[] = $offset;

$stmt_users = $conn->prepare($users_request);
$stmt_users->bind_param($types, ...$params);
$stmt_users->execute();
$result = $stmt_users->get_result();

$start_index = ($page - 1) * $records_per_page;
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
    <style>
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <?php include '../admin/topbar.php'; ?>
    <div class="users-main-container">
        <h1><?= $contest_heading; ?></h1>

        <?php if ($message): ?>
        <div class="message <?= $is_error ? 'error' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

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
                <?php if ($is_admin_or_manager): ?>
                <th>Действия</th>
                <?php endif; ?>
            </tr>
            <?php $i = 1; while($row = $result->fetch_assoc()){ ?>
                <tr>
                    <td><?= $start_index + $i++; ?></td>
                    <td><?= htmlspecialchars($row['first_name']); ?></td>
                    <td><?= htmlspecialchars($row['last_name']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td>
                        <?php if (!empty($row['image_path'])): ?>
                            <img src="uploades/<?= htmlspecialchars($row['image_path']); ?>" width="50" height="50" alt="User Image">
                        <?php else: ?>
                            Нет рисунка
                        <?php endif; ?>
                    </td>
                    
                    <td>
                        <?php if ($is_all_users_view): ?>
                            <a href="user_contests.php?user_id=<?= $row['id']; ?>">
                                Участвует в <?= $row['contest_count']; ?> конкурс(ах)
                            </a>
                        <?php else: ?>
                            <a href="user_contests.php?user_id=<?= $row['id']; ?>">
                                <?= htmlspecialchars($row['contest']); ?>
                            </a>
                        <?php endif; ?>
                    </td>

                    <?php if ($is_admin_or_manager): ?>
                    <td>
                        <a href="update_users.php?id=<?= $row['id']; ?>&id_contest=<?= $is_all_users_view ? '' : $row['contest_id']; ?>">Изменить</a> | 
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
