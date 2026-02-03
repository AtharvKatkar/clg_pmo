<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
include 'includes/header.php';

// Only admins can access this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    echo '<div class="alert alert-danger container mt-4">Invalid Task ID.</div>';
    include 'includes/footer.php';
    exit();
}

// Fetch Task Details
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    echo '<div class="alert alert-danger container mt-4">Task not found.</div>';
    include 'includes/footer.php';
    exit();
}

$project_id = $task['project_id'];

// Fetch Current Assignments
$stmt = $pdo->prepare("SELECT user_id FROM task_assignments WHERE task_id = ?");
$stmt->execute([$task_id]);
$current_assignees = $stmt->fetchAll(PDO::FETCH_COLUMN);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $assignees = $_POST['assignees'] ?? []; // Array of user IDs

    if (empty($title)) {
        $message = "Task title is required.";
        $messageType = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            // Update Task
            $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, status = ?, priority = ?, due_date = ? WHERE id = ?");
            $stmt->execute([$title, $description, $status, $priority, $due_date, $task_id]);

            // Update Assignments (Delete all, then re-insert)
            $stmt = $pdo->prepare("DELETE FROM task_assignments WHERE task_id = ?");
            $stmt->execute([$task_id]);

            if (!empty($assignees)) {
                $assignmentStmt = $pdo->prepare("INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)");
                foreach ($assignees as $user_id) {
                    $assignmentStmt->execute([$task_id, $user_id]);
                }
            }

            $pdo->commit();
            $message = "Task updated successfully!";
            $messageType = "success";
            
            // Refresh data
            $task['title'] = $title;
            $task['description'] = $description;
            $task['status'] = $status;
            $task['priority'] = $priority;
            $task['due_date'] = $due_date;
            $current_assignees = $assignees;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error updating task: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Fetch all potential assignees
$stmt = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
$users = $stmt->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="view_project.php?id=<?php echo $project_id; ?>" class="text-decoration-none">Project</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Task</li>
            </ol>
        </nav>
        
        <div class="card shadow p-4">
            <div class="mb-4">
                <h2 class="fw-bold">Edit Task</h2>
                <p class="text-secondary">Update task details and assignments.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <?php if ($messageType === 'success'): ?>
                        <a href="view_project.php?id=<?php echo $project_id; ?>" class="alert-link">Return to Project Dashboard</a>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="edit_task.php?id=<?php echo $task_id; ?>" method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Task Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>To Do</option>
                            <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="review" <?php echo $task['status'] === 'review' ? 'selected' : ''; ?>>Review</option>
                            <option value="done" <?php echo $task['status'] === 'done' ? 'selected' : ''; ?>>Done</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="critical" <?php echo $task['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $task['due_date']; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="assignees" class="form-label">Assign Members</label>
                    <select class="form-select" id="assignees" name="assignees[]" multiple size="4">
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $current_assignees) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Hold Ctrl/Cmd to select multiple members.</div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Update Task</button>
                    <a href="view_project.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
