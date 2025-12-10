<?php
session_start();
include '../db_conn.php/db.php';

// Authentication and Authorization
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$is_admin = false;
if (isset($_SESSION['role_id'])) {
    $role_id_session = $_SESSION['role_id'];
    $stmt = $conn->prepare("SELECT role FROM role WHERE role_id = ?");
    $stmt->bind_param("i", $role_id_session);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($role_session = $result->fetch_assoc()) {
        if ($role_session['role'] == 'admin') {
            $is_admin = true;
        }
    }
}

if (!$is_admin) {
    header("Location: dashboard.php?message=Error: You are not authorized to perform this action.");
    exit;
}

$message = '';
$error = '';
$edit_role = null;

// Handle Create and Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = $_POST['role_name'] ?? '';
    $role_id = $_POST['role_id'] ?? null;

    if (!empty($role_name)) {
        if (!empty($role_id)) {
            // Update
            $stmt = $conn->prepare("UPDATE role SET role = ? WHERE role_id = ?");
            $stmt->bind_param("si", $role_name, $role_id);
            if ($stmt->execute()) {
                $message = "Role updated successfully.";
            } else {
                $error = "Error updating role.";
            }
        } else {
            // Create
            $stmt = $conn->prepare("INSERT INTO role (role) VALUES (?)");
            $stmt->bind_param("s", $role_name);
            if ($stmt->execute()) {
                $message = "Role created successfully.";
            } else {
                $error = "Error creating role.";
            }
        }
    } else {
        $error = "Role name cannot be empty.";
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    // Prevent deleting the main admin and user roles if they are protected
    if ($delete_id > 2) {
        $stmt = $conn->prepare("DELETE FROM role WHERE role_id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Role deleted successfully.";
        } else {
            $error = "Error deleting role.";
        }
    } else {
        $error = "This role cannot be deleted.";
    }
}

// Handle Edit
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM role WHERE role_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_role = $result->fetch_assoc();
}

// Fetch all roles
$roles_result = $conn->query("SELECT * FROM role ORDER BY role_id DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Roles</title>
    <link rel="stylesheet" href="auth_style.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body>

<div class="container dashboard-container">
    <?php include 'topbar.php'; ?>

    <div class="content">
        <h1>Manage Roles</h1>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <h3><?php echo $edit_role ? 'Edit Role' : 'Add New Role'; ?></h3>
        <form action="roles.php" method="post">
            <input type="hidden" name="role_id" value="<?php echo $edit_role['role_id'] ?? ''; ?>">
            <div class="form-group">
                <label>Role Name:</label>
                <input type="text" name="role_name" value="<?php echo htmlspecialchars($edit_role['role'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <input type="submit" value="<?php echo $edit_role ? 'Update Role' : 'Add Role'; ?>">
                <?php if ($edit_role): ?>
                    <a href="roles.php">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>

        <h3>All Roles</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Role Name</th>
                <th>Action</th>
            </tr>
            <?php while($role = $roles_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $role['role_id']; ?></td>
                <td><?php echo htmlspecialchars($role['role']); ?></td>
                <td>
                    <a href="roles.php?edit_id=<?php echo $role['role_id']; ?>">Edit</a>
                    <?php if ($role['role_id'] > 2): // Basic protection for first 2 roles ?>
                    | <a href="roles.php?delete_id=<?php echo $role['role_id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

</body>
</html>
