<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
include 'includes/header.php';

// Only admins can access this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $created_by = $_SESSION['user_id'];

    if (empty($title)) {
        $message = "Project title is required.";
        $messageType = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO projects (title, description, status, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $status, $start_date, $end_date, $created_by])) {
                $message = "Project created successfully!";
                $messageType = "success";
            }
        } catch (PDOException $e) {
            $message = "Error creating project: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}
?>

<div class="flex justify-center">
    <div class="w-full max-w-4xl">
        <div class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="dashboard.php" class="text-primary font-medium">Dashboard</a></li>
                <li>Create Project</li>
            </ul>
        </div>
        
        <div class="card bg-base-100 shadow-2xl p-8 border border-base-300">
            <div class="mb-8">
                <h2 class="text-3xl font-black">Create New Project</h2>
                <p class="text-base-content/60">Define the details for your new project.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'danger' ? 'alert-error' : 'alert-success'; ?> shadow-sm mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div>
                        <span><?php echo $message; ?></span>
                        <?php if ($messageType === 'success'): ?>
                            <div class="mt-2"><a href="dashboard.php" class="link font-bold">Return to Dashboard</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form action="create_project.php" method="POST" class="space-y-6">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Project Title <span class="text-error">*</span></span></label>
                    <input type="text" name="title" class="input input-bordered w-full" placeholder="Enter project title" required />
                </div>
                
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Description</span></label>
                    <textarea name="description" class="textarea textarea-bordered h-24" placeholder="Enter project description"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Status</span></label>
                        <select name="status" class="select select-bordered w-full">
                            <option value="planned">Planned</option>
                            <option value="active">Active</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Start Date</span></label>
                        <input type="date" name="start_date" class="input input-bordered w-full" />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">End Date</span></label>
                        <input type="date" name="end_date" class="input input-bordered w-full" />
                    </div>
                </div>

                <div class="flex gap-4 mt-8">
                    <button type="submit" class="btn btn-primary flex-1 shadow-lg font-black">Create Project</button>
                    <a href="dashboard.php" class="btn btn-ghost flex-1">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
