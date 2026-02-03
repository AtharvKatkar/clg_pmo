<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
include 'includes/header.php';

// Only admins can access this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$project_id = $_GET['project_id'] ?? null;

// Fetch Title for Context
$project_title = '';
if ($project_id) {
    $stmt = $pdo->prepare("SELECT title FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    $project_title = $project ? $project['title'] : '';
}

if (!$project_id || !$project_title) {
    echo '<div class="alert alert-danger container mt-4">Invalid Project ID.</div>';
    include 'includes/footer.php';
    exit();
}

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

            // Insert Task
            $stmt = $pdo->prepare("INSERT INTO tasks (project_id, title, description, status, priority, due_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$project_id, $title, $description, $status, $priority, $due_date]);
            $task_id = $pdo->lastInsertId();

            // Insert Assignments
            if (!empty($assignees)) {
                $assignmentStmt = $pdo->prepare("INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)");
                foreach ($assignees as $user_id) {
                    $assignmentStmt->execute([$task_id, $user_id]);
                }
            }

            $pdo->commit();
            $message = "Task created successfully!";
            $messageType = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error creating task: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Fetch all potential assignees (Members & Admins)
// In a real app, you might restrict this to team members, but for now allow all.
$stmt = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
$users = $stmt->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="view_project.php?id=<?php echo $project_id; ?>" class="text-decoration-none"><?php echo htmlspecialchars($project_title); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Task</li>
            </ol>
        </nav>
        
        <div class="card shadow p-4">
            <div class="mb-4">
                <h2 class="fw-bold">Create New Task</h2>
                <p class="text-secondary">Add a task to <strong><?php echo htmlspecialchars($project_title); ?></strong></p>
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

            <form action="create_task.php?project_id=<?php echo $project_id; ?>" method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Task Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="todo">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="review">Review</option>
                            <option value="done">Done</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="assignees" class="form-label">Assign Members</label>
                    <select class="form-select" id="assignees" name="assignees[]" multiple size="4">
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Hold Ctrl/Cmd to select multiple members.</div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Create Task</button>
                    <a href="view_project.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
