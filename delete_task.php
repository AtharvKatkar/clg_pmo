<?php
require_once 'config/db.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$task_id = $_GET['id'] ?? null;
$project_id = null;

if ($task_id) {
    // Get project ID for redirection before deleting
    $stmt = $pdo->prepare("SELECT project_id FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if ($task) {
        $project_id = $task['project_id'];

        // Delete Task (Cascades to assignments)
        $msg = "Error deleting task.";
        $type = "danger";
        
        try {
            $delStmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            if ($delStmt->execute([$task_id])) {
               // Success
            }
        } catch (PDOException $e) {
            // Log error
        }
    }
}

if ($project_id) {
    header("Location: view_project.php?id=" . $project_id);
} else {
    header("Location: dashboard.php");
}
exit();
?>
