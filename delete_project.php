<?php
require_once 'config/db.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $project_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Verify project belongs to this admin before deleting
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND created_by = ?");
    
    try {
        if ($stmt->execute([$project_id, $user_id])) {
            // Success - could add a flash message here if we had a mechanism
        }
    } catch (PDOException $e) {
        // Error handling
    }
}

header("Location: dashboard.php");
exit();
?>
