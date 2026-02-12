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

<div class="flex justify-center">
    <div class="w-full max-w-2xl">
        <div class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="dashboard.php" class="text-primary font-medium">Dashboard</a></li>
                <li><a href="view_project.php?id=<?php echo $task['project_id']; ?>" class="text-primary font-medium">Project</a></li>
                <li>Update Status</li>
            </ul>
        </div>
        
        <div class="card bg-base-100 shadow-2xl p-8 border border-base-300">
            <div class="mb-8">
                <h2 class="text-3xl font-black">Update Task Status</h2>
                <p class="text-xl opacity-60 mt-2 font-medium"><?php echo htmlspecialchars($task['title']); ?></p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'danger' ? 'alert-error' : 'alert-success'; ?> shadow-sm mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div>
                        <span><?php echo $message; ?></span>
                        <?php if ($messageType === 'success'): ?>
                            <div class="mt-2"><a href="view_project.php?id=<?php echo $task['project_id']; ?>" class="link font-bold">Return to Project</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">New Status</span></label>
                    <select name="status" class="select select-bordered w-full text-lg">
                        <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>To Do</option>
                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="review" <?php echo $task['status'] === 'review' ? 'selected' : ''; ?>>Review</option>
                        <option value="done" <?php echo $task['status'] === 'done' ? 'selected' : ''; ?>>Done</option>
                    </select>
                </div>
                
                <div class="flex flex-col gap-4 mt-8">
                    <button type="submit" class="btn btn-primary w-full shadow-lg font-black">Update Status</button>
                    <a href="view_project.php?id=<?php echo $task['project_id']; ?>" class="btn btn-ghost w-full">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
