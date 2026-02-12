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

<div class="flex justify-center">
    <div class="w-full max-w-4xl">
        <div class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="dashboard.php" class="text-primary font-medium">Dashboard</a></li>
                <li><a href="view_project.php?id=<?php echo $project_id; ?>" class="text-primary font-medium">Project</a></li>
                <li>Edit Task</li>
            </ul>
        </div>
        
        <div class="card bg-base-100 shadow-2xl p-8 border border-base-300">
            <div class="mb-8">
                <h2 class="text-3xl font-black">Edit Task</h2>
                <p class="text-base-content/60">Update task details and assignments.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'danger' ? 'alert-error' : 'alert-success'; ?> shadow-sm mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div>
                        <span><?php echo $message; ?></span>
                        <?php if ($messageType === 'success'): ?>
                            <div class="mt-2"><a href="view_project.php?id=<?php echo $project_id; ?>" class="link font-bold">Return to Project Dashboard</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form action="edit_task.php?id=<?php echo $task_id; ?>" method="POST" class="space-y-6">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Task Title <span class="text-error">*</span></span></label>
                    <input type="text" name="title" class="input input-bordered w-full" value="<?php echo htmlspecialchars($task['title']); ?>" required />
                </div>
                
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Description</span></label>
                    <textarea name="description" class="textarea textarea-bordered h-24"><?php echo htmlspecialchars($task['description']); ?></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Status</span></label>
                        <select name="status" class="select select-bordered w-full">
                            <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>To Do</option>
                            <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="review" <?php echo $task['status'] === 'review' ? 'selected' : ''; ?>>Review</option>
                            <option value="done" <?php echo $task['status'] === 'done' ? 'selected' : ''; ?>>Done</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Priority</span></label>
                        <select name="priority" class="select select-bordered w-full">
                            <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="critical" <?php echo $task['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Due Date</span></label>
                        <input type="date" name="due_date" class="input input-bordered w-full" value="<?php echo $task['due_date']; ?>" />
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Assign Members</span></label>
                    <select name="assignees[]" multiple class="select select-bordered h-32">
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $current_assignees) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="label">
                        <span class="label-text-alt opacity-60">Hold Ctrl/Cmd to select multiple members</span>
                    </label>
                </div>

                <div class="flex gap-4 mt-8">
                    <button type="submit" class="btn btn-primary flex-1 shadow-lg font-black">Update Task</button>
                    <a href="view_project.php?id=<?php echo $project_id; ?>" class="btn btn-ghost flex-1">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
