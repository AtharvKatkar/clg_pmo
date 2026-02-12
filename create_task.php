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

<div class="flex justify-center">
    <div class="w-full max-w-4xl">
        <div class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="dashboard.php" class="text-primary font-medium">Dashboard</a></li>
                <li><a href="view_project.php?id=<?php echo $project_id; ?>" class="text-primary font-medium"><?php echo htmlspecialchars($project_title); ?></a></li>
                <li>Create Task</li>
            </ul>
        </div>
        
        <div class="card bg-base-100 shadow-2xl p-8 border border-base-300">
            <div class="mb-8">
                <h2 class="text-3xl font-black">Create New Task</h2>
                <p class="text-base-content/60">Add a task to <span class="badge badge-primary font-bold"><?php echo htmlspecialchars($project_title); ?></span></p>
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

            <form action="create_task.php?project_id=<?php echo $project_id; ?>" method="POST" class="space-y-6">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Task Title <span class="text-error">*</span></span></label>
                    <input type="text" name="title" class="input input-bordered w-full" placeholder="Enter task title" required />
                </div>
                
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Description</span></label>
                    <textarea name="description" class="textarea textarea-bordered h-24" placeholder="Enter task description"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Status</span></label>
                        <select name="status" class="select select-bordered w-full">
                            <option value="todo">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="review">Review</option>
                            <option value="done">Done</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Priority</span></label>
                        <select name="priority" class="select select-bordered w-full">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Due Date</span></label>
                        <input type="date" name="due_date" class="input input-bordered w-full" />
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Assign Members</span></label>
                    <select name="assignees[]" multiple class="select select-bordered h-32">
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="label">
                        <span class="label-text-alt opacity-60">Hold Ctrl/Cmd to select multiple members</span>
                    </label>
                </div>

                <div class="flex gap-4 mt-8">
                    <button type="submit" class="btn btn-primary flex-1 shadow-lg font-black">Create Task</button>
                    <a href="view_project.php?id=<?php echo $project_id; ?>" class="btn btn-ghost flex-1">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
