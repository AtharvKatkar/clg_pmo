<?php
require_once 'config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('planned', 'active', 'completed', 'on_hold') NOT NULL DEFAULT 'planned',
        start_date DATE NULL,
        end_date DATE NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);
    echo "Projects table created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
