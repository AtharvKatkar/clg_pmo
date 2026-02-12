<?php
require_once 'config/db.php';

try {
    // COMMENTS Table
    $sqlComments = "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlComments);
    echo "Comments table created successfully.<br>";

    // COMMENT REACTIONS Table
    $sqlReactions = "CREATE TABLE IF NOT EXISTS comment_reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction_type VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_reaction (comment_id, user_id, reaction_type),
        FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlReactions);
    echo "Comment Reactions table created successfully.<br>";

} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
