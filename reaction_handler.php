<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'] ?? null;
    $reaction_type = $_POST['reaction_type'] ?? null;
    $user_id = $_SESSION['user_id'];

    if ($comment_id && $reaction_type) {
        try {
            // Check if reaction already exists
            $stmt = $pdo->prepare("SELECT id FROM comment_reactions WHERE comment_id = ? AND user_id = ? AND reaction_type = ?");
            $stmt->execute([$comment_id, $user_id, $reaction_type]);
            $existing = $stmt->fetch();

            if ($existing) {
                // If it exists, remove it (toggle off)
                $stmt = $pdo->prepare("DELETE FROM comment_reactions WHERE id = ?");
                $stmt->execute([$existing['id']]);
                echo json_encode(['status' => 'success', 'action' => 'removed']);
            } else {
                // If not, add it (toggle on)
                $stmt = $pdo->prepare("INSERT INTO comment_reactions (comment_id, user_id, reaction_type) VALUES (?, ?, ?)");
                $stmt->execute([$comment_id, $user_id, $reaction_type]);
                echo json_encode(['status' => 'success', 'action' => 'added']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
