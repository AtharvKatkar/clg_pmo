<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

// Enable full width layout and hide default navbar
$full_width_layout = true;
$hide_navbar = true;
include 'includes/header.php';

// Only admins can access this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch members created by this admin
$stmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE created_by = ? AND role = 'member'");
$stmt->execute([$user_id]);
$members = $stmt->fetchAll();
?>

<!-- DRAWER LAYOUT START -->
<div class="drawer lg:drawer-open bg-base-100 text-base-content min-h-screen">
  <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
  
  <div class="drawer-content flex flex-col">
    <!-- Mobile Header -->
    <div class="w-full navbar bg-base-200 lg:hidden mb-4 shadow-sm">
        <div class="flex-none">
            <label for="my-drawer-2" class="btn btn-square btn-ghost">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </label>
        </div> 
        <div class="flex-1 px-2 mx-2 font-bold text-lg">PMO App</div>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="p-6 md:p-10">
        <div class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="dashboard.php" class="text-primary font-medium">Dashboard</a></li>
                <li>Manage Team</li>
            </ul>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div>
                <h1 class="text-4xl font-black mb-1">Manage Your Team</h1>
                <p class="text-base-content/60">View and manage the team members you have added.</p>
            </div>
            <a href="create_member.php" class="btn btn-primary shadow-lg font-black">
                <i class="bi bi-person-plus text-lg mr-2"></i>Create New Member
            </a>
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-0 md:p-6">
                <div class="p-6 md:p-0">
                    <h3 class="text-2xl font-bold mb-4">Team Members</h3>
                    <?php if (count($members) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra w-full">
                                <thead class="bg-base-200">
                                    <tr>
                                        <th class="rounded-tl-lg">ID</th>
                                        <th>Username</th>
                                        <th>Joined Date</th>
                                        <th class="rounded-tr-lg">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                        <tr class="hover">
                                            <td class="font-mono text-xs opacity-50"><?php echo $member['id']; ?></td>
                                            <td class="font-bold"><?php echo htmlspecialchars($member['username']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                            <td>
                                                <div class="join">
                                                    <button class="btn btn-ghost btn-xs join-item">View</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 bg-base-200/50 rounded-lg">
                            <i class="bi bi-people text-6xl opacity-20"></i>
                            <p class="mt-4 text-xl font-medium opacity-40 text-center px-4">No members created yet. Click "Create New Member" to add someone!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
  </div> 

  <!-- SIDEBAR (Drawer Side) -->
  <?php include 'includes/sidebar.php'; ?>
  
</div>
<!-- DRAWER LAYOUT END -->

<?php include 'includes/footer.php'; ?>
