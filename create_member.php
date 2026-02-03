<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

// Only admins can access this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = 'member'; // Admins create members
    $created_by = $_SESSION['user_id'];

    if (empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
        $messageType = "danger";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $message = "Username already taken.";
            $messageType = "danger";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_by) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $hashed_password, $role, $created_by])) {
                $message = "Team member created successfully!";
                $messageType = "success";
                // Optional: Redirect to manage team after short delay or let them stay to create more
                // For now, let's keep them here to see the message, but update back button
            } else {
                $message = "Something went wrong. Please try again.";
                $messageType = "danger";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-primary text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="manage_team.php" class="text-primary text-decoration-none">Manage Team</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Member</li>
            </ol>
        </nav>
        
        <div class="card shadow p-4">
            <div class="mb-4">
                <h2 class="fw-bold">Create Team Member</h2>
                <p class="text-secondary">Register a new member for your team. They will be linked to your admin account.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="create_member.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Member Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Temporary Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Create Member</button>
                    <a href="manage_team.php" class="btn btn-outline-secondary">Back to Team Management</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
