<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="drawer-side z-50">
    <label for="my-drawer-2" aria-label="close sidebar" class="drawer-overlay"></label> 
    <ul class="menu p-4 w-80 min-h-full bg-base-200 text-base-content flex flex-col gap-2">
        <!-- Sidebar Content -->
        <div class="flex items-center gap-3 px-2 mb-8 mt-2">
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center font-bold text-primary-content shadow-lg shadow-primary/30">
                <i class="bi bi-rocket-takeoff-fill"></i>
            </div>
            <div>
                <h1 class="font-bold text-lg leading-tight">Hello<br><span class="text-xs font-normal opacity-70">Workspace</span></h1>
            </div>
        </div>

        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active shadow-lg' : ''; ?>">
                <i class="bi bi-grid-fill"></i> 
                Dashboard
            </a>
        </li>
        
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li>
            <a href="manage_team.php" class="<?php echo $current_page == 'manage_team.php' ? 'active shadow-lg' : ''; ?>">
                <i class="bi bi-people-fill"></i> 
                Manage Team
            </a>
        </li>
        <?php endif; ?>

        <li>
            <a href="logout.php" class="text-error hover:bg-error/10">
                <i class="bi bi-box-arrow-right"></i> 
                Logout
            </a>
        </li>

        <div class="mt-auto p-4 rounded-xl bg-base-300 relative overflow-hidden">
            <div class="absolute -right-2 -top-2 w-16 h-16 bg-primary/20 rounded-full blur-xl"></div>
            <h3 class="font-bold mb-2 text-sm">Upgrade your account</h3>
            <p class="text-xs opacity-60 mb-3">Get full access to all features</p>
            <button class="btn btn-xs btn-primary w-full shadow-md">Upgrade</button>
        </div>
    </ul>
</div>
