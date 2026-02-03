<?php
require_once 'config/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'admin'; // Always admin for public registration as per requirements

    if (empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
        $messageType = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
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
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_by) VALUES (?, ?, ?, NULL)");
            if ($stmt->execute([$username, $hashed_password, $role])) {
                $message = "Admin account created successfully! You can now login.";
                $messageType = "success";
            } else {
                $message = "Something went wrong. Please try again.";
                $messageType = "danger";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow p-4">
            <div class="text-center mb-4">
                <i class="bi bi-person-plus-fill text-primary display-4"></i>
                <h2 class="mt-2 fw-bold">Admin Sign Up</h2>
                <p class="text-secondary">Create your admin account to manage your team</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Register as Admin</button>
                </div>
            </form>
            <div class="mt-4 text-center">
                <p class="text-secondary">Already have an account? <a href="login.php" class="text-primary text-decoration-none">Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
