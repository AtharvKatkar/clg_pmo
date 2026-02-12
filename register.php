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

<div class="flex justify-center items-center min-h-[80vh]">
    <div class="w-full max-w-lg">
        <div class="card bg-base-100 shadow-2xl p-8 border border-base-300">
            <div class="text-center mb-8">
                <i class="bi bi-person-plus-fill text-primary text-5xl"></i>
                <h2 class="mt-4 text-3xl font-black">Admin Sign Up</h2>
                <p class="text-base-content/60">Create your admin account to manage your team</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'danger' ? 'alert-error' : 'alert-success'; ?> shadow-sm mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Username</span></label>
                    <input type="text" name="username" class="input input-bordered w-full" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Password</span></label>
                    <input type="password" name="password" class="input input-bordered w-full" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Confirm Password</span></label>
                    <input type="password" name="confirm_password" class="input input-bordered w-full" required />
                </div>
                <button type="submit" class="btn btn-primary w-full mt-6 shadow-lg">Register as Admin</button>
            </form>
            
            <div class="mt-8 text-center border-t border-base-200 pt-6">
                <p class="text-sm opacity-70">Already have an account? <a href="login.php" class="link link-primary font-bold">Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
