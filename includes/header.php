<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMO - Project Management Office</title>
    <title>PMO - Project Management Office</title>
    <!-- Tailwind CSS and DaisyUI CDN -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap Icons (still useful for icons) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

</head>
<body class="bg-base-200 min-h-screen">
<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
?>

<?php if (!isset($hide_navbar) || !$hide_navbar): ?>
<div class="navbar bg-primary text-primary-content shadow-lg mb-8">
    <div class="container mx-auto">
        <div class="flex-1">
            <a class="btn btn-ghost text-xl font-bold gap-2" href="index.php">
                <i class="bi bi-rocket-takeoff-fill"></i>
                PMO App
            </a>
        </div>
        <div class="flex-none lg:hidden">
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="4 6h16M4 12h16M4 18h7" /></svg>
                </label>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 text-base-content rounded-box w-52">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <?php if($_SESSION['role'] === 'admin'): ?>
                            <li><a href="manage_team.php">Manage Team</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php" class="text-error">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Admin Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="flex-none hidden lg:block">
            <ul class="menu menu-horizontal px-1 gap-2">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="rounded-lg">Dashboard</a></li>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <li><a href="manage_team.php" class="rounded-lg">Manage Team</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="btn btn-error btn-sm text-white ml-2">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="rounded-lg">Login</a></li>
                    <li><a href="register.php" class="btn btn-ghost btn-sm">Admin Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!isset($full_width_layout) || !$full_width_layout): ?>
<div class="container mx-auto px-4 pb-12">
<?php endif; ?>
