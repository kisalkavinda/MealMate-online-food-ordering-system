<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if user id is provided
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Prevent admin from deleting their own account
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "⚠️ You cannot delete your own account!";
        header("Location: manage_users.php");
        exit();
    }

    // Delete query
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ User deleted successfully.";
    } else {
        $_SESSION['error'] = "❌ Failed to delete user. Please try again.";
    }

    $stmt->close();
    $conn->close();

    header("Location: manage_users.php");
    exit();
} else {
    $_SESSION['error'] = "⚠️ Invalid request.";
    header("Location: manage_users.php");
    exit();
}
