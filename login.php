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
?>

<?php include 'includes/header.php'; ?>

<!-- Custom animations for view switching -->
<style>
    .view-section { display: none; }
    .view-section.active { display: block; animation: fadeIn 0.4s ease; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="flex justify-center items-center min-h-[70vh]">
    <div class="w-full max-w-md">
        <div class="flex bg-base-300 p-1 rounded-full mb-8 shadow-inner">
            <button id="toggle-admin" class="flex-1 py-2 px-4 rounded-full font-bold transition-all active bg-primary text-white" onclick="switchView('admin')">Admin Login</button>
            <button id="toggle-user" class="flex-1 py-2 px-4 rounded-full font-bold transition-all text-base-content/60" onclick="switchView('user')">User Login</button>
        </div>

        <div class="card bg-base-100 shadow-2xl p-8 border border-base-300">
            <!-- Admin View -->
            <div id="admin-view" class="view-section active text-center">
                <i class="bi bi-shield-lock-fill text-primary text-5xl"></i>
                <h2 class="mt-4 text-3xl font-black">Admin Login</h2>
                <p class="text-base-content/60 mb-6">Manage your projects and team</p>
            </div>

            <!-- User View -->
            <div id="user-view" class="view-section text-center">
                <i class="bi bi-person-workspace text-primary text-5xl"></i>
                <h2 class="mt-4 text-3xl font-black">User Login</h2>
                <p class="text-base-content/60 mb-6">Track your tasks and progress</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'danger' ? 'alert-error' : 'alert-success'; ?> shadow-sm mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Username</span></label>
                    <div class="join">
                        <span class="join-item bg-base-200 px-4 flex items-center border border-base-300 border-r-0"><i class="bi bi-person opacity-50"></i></span>
                        <input type="text" name="username" placeholder="Enter username" class="input input-bordered join-item w-full" required />
                    </div>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Password</span></label>
                    <div class="join">
                        <span class="join-item bg-base-200 px-4 flex items-center border border-base-300 border-r-0"><i class="bi bi-lock opacity-50"></i></span>
                        <input type="password" name="password" placeholder="Enter password" class="input input-bordered join-item w-full" required />
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-full mt-6 shadow-lg">Sign In</button>
            </form>

            <div id="admin-footer" class="view-section active mt-6 text-center">
                <p class="text-sm opacity-70">Don't have an admin account? <a href="register.php" class="link link-primary font-bold">Sign up</a></p>
            </div>
            
            <div id="user-footer" class="view-section mt-6 text-center">
                <p class="text-sm opacity-70 italic">Contact your administrator for access.</p>
            </div>
        </div>
    </div>
</div>

<script>
function switchView(view) {
    // Buttons Styling
    const adminBtn = document.getElementById('toggle-admin');
    const userBtn = document.getElementById('toggle-user');
    
    // Content Views
    const views = ['admin-view', 'user-view', 'admin-footer', 'user-footer'];
    views.forEach(id => document.getElementById(id).classList.remove('active'));

    if (view === 'admin') {
        adminBtn.classList.add('bg-primary', 'text-white', 'active');
        adminBtn.classList.remove('text-base-content/60');
        userBtn.classList.remove('bg-primary', 'text-white', 'active');
        userBtn.classList.add('text-base-content/60');
        document.getElementById('admin-view').classList.add('active');
        document.getElementById('admin-footer').classList.add('active');
    } else {
        userBtn.classList.add('bg-primary', 'text-white', 'active');
        userBtn.classList.remove('text-base-content/60');
        adminBtn.classList.remove('bg-primary', 'text-white', 'active');
        adminBtn.classList.add('text-base-content/60');
        document.getElementById('user-view').classList.add('active');
        document.getElementById('user-footer').classList.add('active');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
