<?php
require_once 'config/db.php';

try {
    // TASKS Table
    $sqlTasks = "CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('todo', 'in_progress', 'review', 'done') NOT NULL DEFAULT 'todo',
        priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
        due_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlTasks);
    echo "Tasks table created successfully.<br>";

    // TASK ASSIGNMENTS Table (Many-to-Many)
    $sqlAssignments = "CREATE TABLE IF NOT EXISTS task_assignments (
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        PRIMARY KEY (task_id, user_id),
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlAssignments);
    echo "Task Assignments table created successfully.<br>";

} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
