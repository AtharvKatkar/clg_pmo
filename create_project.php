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

<div class="row justify-content-center">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Project</li>
            </ol>
        </nav>
        
        <div class="card shadow p-4">
            <div class="mb-4">
                <h2 class="fw-bold">Create New Project</h2>
                <p class="text-secondary">Define the details for your new project.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <?php if ($messageType === 'success'): ?>
                        <a href="dashboard.php" class="alert-link">Return to Dashboard</a>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="create_project.php" method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Project Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="planned">Planned</option>
                            <option value="active">Active</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Create Project</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
