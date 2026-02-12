<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? null;
    $user_id = $_SESSION['user_id'];
    $comment = trim($_POST['comment'] ?? '');

    if ($task_id && !empty($comment)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$task_id, $user_id, $comment]);
            
            header("Location: view_task.php?id=" . $task_id);
            exit();
        } catch (PDOException $e) {
            die("Error adding comment: " . $e->getMessage());
        }
    } else {
        header("Location: dashboard.php");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
