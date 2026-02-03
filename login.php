<?php
require_once 'config/db.php';

// Start session to check if user is already logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Invalid username or password.";
            $messageType = "danger";
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow p-4">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock-fill text-primary display-4"></i>
                <h2 class="mt-2 fw-bold">Login</h2>
                <p class="text-secondary">Access your project management dashboard</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Sign In</button>
                </div>
            </form>
            <div class="mt-4 text-center">
                <p class="text-secondary">Don't have an admin account? <a href="register.php" class="text-primary text-decoration-none">Sign up</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
