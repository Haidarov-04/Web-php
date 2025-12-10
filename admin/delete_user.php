<?php
session_start();
include '../db_conn.php/db.php';

// If the user is not logged in redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if the user is an admin
$is_admin = false;
if (isset($_SESSION['role_id'])) {
    $role_id = $_SESSION['role_id'];
    $stmt = $conn->prepare("SELECT role FROM role WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($role = $result->fetch_assoc()) {
        if ($role['role'] == 'admin') {
            $is_admin = true;
        }
    }
}

if (!$is_admin) {
    header("Location: dashboard.php?message=Error: You are not authorized to perform this action.");
    exit;
}

// Get the user ID from the GET request
$user_id = $_GET['id'] ?? null;

if ($user_id) {
    // Prevent admin from deleting their own account
    if ($user_id == $_SESSION['user_id']) {
        header("Location: dashboard.php?message=Error: You cannot delete your own account.");
        exit;
    }

    // Delete the user
    $stmt = $conn->prepare("DELETE FROM acces_users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        header("Location: dashboard.php?message=User deleted successfully.");
    } else {
        header("Location: dashboard.php?message=Error: Could not delete user.");
    }
} else {
    header("Location: dashboard.php?message=Error: User ID not specified.");
}
?>