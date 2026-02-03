<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
include 'includes/header.php';

$task_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!$task_id) {
    echo '<div class="alert alert-danger container mt-4">Invalid Task ID.</div>';
    include 'includes/footer.php';
    exit();
}

// 1. Fetch Task
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    echo '<div class="alert alert-danger container mt-4">Task not found.</div>';
    include 'includes/footer.php';
    exit();
}

// 2. Security Check: If Member, must be assigned
if ($role !== 'admin') {
    $stmt = $pdo->prepare("SELECT 1 FROM task_assignments WHERE task_id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    if (!$stmt->fetch()) {
        echo '<div class="alert alert-danger container mt-4">Access Denied. You are not assigned to this task.</div>';
        include 'includes/footer.php';
        exit();
    }
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$status, $task_id]);
        $message = "Task status updated successfully!";
        $messageType = "success";
        $task['status'] = $status; // Update display
    } catch (PDOException $e) {
        $message = "Error updating status: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="view_project.php?id=<?php echo $task['project_id']; ?>" class="text-decoration-none">Project</a></li>
                <li class="breadcrumb-item active" aria-current="page">Update Status</li>
            </ol>
        </nav>
        
        <div class="card shadow p-4">
            <h3 class="fw-bold mb-3">Update Task Status</h3>
            <p class="lead"><?php echo htmlspecialchars($task['title']); ?></p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <?php if ($messageType === 'success'): ?>
                        <a href="view_project.php?id=<?php echo $task['project_id']; ?>" class="alert-link">Return to Project</a>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="status" class="form-label">Current Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>To Do</option>
                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="review" <?php echo $task['status'] === 'review' ? 'selected' : ''; ?>>Review</option>
                        <option value="done" <?php echo $task['status'] === 'done' ? 'selected' : ''; ?>>Done</option>
                    </select>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Update Status</button>
                    <a href="view_project.php?id=<?php echo $task['project_id']; ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
