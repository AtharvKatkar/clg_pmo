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

<div class="flex justify-center">
    <div class="w-full max-w-2xl">
        <div class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="dashboard.php" class="text-primary font-medium">Dashboard</a></li>
                <li><a href="manage_team.php" class="text-primary font-medium">Manage Team</a></li>
                <li>Create Member</li>
            </ul>
        </div>
        
        <div class="card bg-base-100 shadow-2xl p-8 border border-base-300">
            <div class="mb-8">
                <h2 class="text-3xl font-black">Create Team Member</h2>
                <p class="text-base-content/60">Register a new member for your team. They will be linked to your admin account.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'danger' ? 'alert-error' : 'alert-success'; ?> shadow-sm mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form action="create_member.php" method="POST" class="space-y-6">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Member Username</span></label>
                    <input type="text" name="username" class="input input-bordered w-full" placeholder="Enter username" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Temporary Password</span></label>
                    <input type="password" name="password" class="input input-bordered w-full" placeholder="Enter password" required />
                </div>
                <div class="flex flex-col gap-4 mt-8">
                    <button type="submit" class="btn btn-primary w-full shadow-lg font-black">Create Member</button>
                    <a href="manage_team.php" class="btn btn-ghost w-full">Back to Team Management</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
