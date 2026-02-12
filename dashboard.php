<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Fetch stats and lists based on role
$projects = [];
$total_projects = 0;
$active_projects = 0;

if ($role === 'admin') {
    // Admin sees all projects they created
    // Check if table exists first to avoid error on fresh install before migration
    try {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE created_by = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $projects = $stmt->fetchAll();
        
        $total_projects = count($projects);
        $active_projects = count(array_filter($projects, function($p) {
            return $p['status'] === 'active';
        }));
    } catch (PDOException $e) {
        // Table might not exist yet
    }
} else {
    // Member logic
    // 1. Get Admin ID
    $stmt = $pdo->prepare("SELECT created_by FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $userRow = $stmt->fetch();
    $admin_id = $userRow['created_by'];

    // 2. Fetch Projects created by Admin
    if ($admin_id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM projects WHERE created_by = ? ORDER BY created_at DESC");
            $stmt->execute([$admin_id]);
            $projects = $stmt->fetchAll();
        } catch (PDOException $e) {}
    }

    // 3. Stats for Member (Assigned Tasks)
    try {
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks t 
                               JOIN task_assignments ta ON t.id = ta.task_id 
                               WHERE ta.user_id = ? 
                               GROUP BY status");
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll();
        
        foreach ($rows as $row) {
            if ($row['status'] === 'done') {
                $active_projects += $row['count']; // Reusing variable for "Completed Tasks"
            } else {
                $total_projects += $row['count']; // Reusing variable for "Pending Tasks"
            }
        }
        // Total Assigned Tasks
        // We will repurpose the stats cards labels in the HTML
    } catch (PDOException $e) {}
}
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
    <div>
        <h1 class="text-4xl font-black mb-1">Dashboard</h1>
        <p class="text-base-content/70">Welcome back, <span class="font-bold text-primary"><?php echo htmlspecialchars($username); ?></span>!</p>
    </div>
    <?php if ($role === 'admin'): ?>
        <a href="create_project.php" class="btn btn-primary shadow-lg">
            <i class="bi bi-plus-lg"></i>
            Create New Project
        </a>
    <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <?php if ($role === 'admin'): ?>
    <div class="stats shadow bg-primary text-primary-content">
        <div class="stat">
            <div class="stat-title text-primary-content/70">Total Projects</div>
            <div class="stat-value"><?php echo $total_projects; ?></div>
            <div class="stat-desc text-primary-content/60">All time projects</div>
        </div>
    </div>
    <div class="stats shadow bg-success text-success-content">
        <div class="stat">
            <div class="stat-title text-success-content/70">Active Projects</div>
            <div class="stat-value"><?php echo $active_projects; ?></div>
            <div class="stat-desc text-success-content/60">Currently in progress</div>
        </div>
    </div>
    <div class="stats shadow bg-info text-info-content">
        <div class="stat">
            <div class="stat-title text-info-content/70">Pending</div>
            <div class="stat-value"><?php echo $total_projects - $active_projects; ?></div>
            <div class="stat-desc text-info-content/60">Planned or on hold</div>
        </div>
    </div>
    <?php else: ?>
    <div class="stats shadow bg-primary text-primary-content">
        <div class="stat">
            <div class="stat-title text-primary-content/70">Team Projects</div>
            <div class="stat-value"><?php echo count($projects); ?></div>
            <div class="stat-desc text-primary-content/60">Projects created by Admin</div>
        </div>
    </div>
    <div class="stats shadow bg-warning text-warning-content">
        <div class="stat">
            <div class="stat-title text-warning-content/70">My Pending Tasks</div>
            <div class="stat-value"><?php echo $total_projects; ?></div>
            <div class="stat-desc text-warning-content/60">To Do / In Progress</div>
        </div>
    </div>
    <div class="stats shadow bg-success text-success-content">
        <div class="stat">
            <div class="stat-title text-success-content/70">My Completed Tasks</div>
            <div class="stat-value"><?php echo $active_projects; ?></div>
            <div class="stat-desc text-success-content/60">Tasks marked as Done</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Projects Table -->
<div class="card bg-base-100 shadow-xl overflow-hidden">
    <div class="card-body p-0">
        <div class="bg-base-200/50 px-6 py-4 border-b border-base-300">
            <h2 class="card-title font-bold"><?php echo $role === 'admin' ? 'My Projects' : 'My Team\'s Projects'; ?></h2>
        </div>
        <?php if (count($projects) > 0): ?>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead class="bg-base-200">
                        <tr>
                            <th>Project Name</th>
                            <th>Status</th>
                            <th>Timeline</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr class="hover">
                                <td class="font-bold text-primary"><?php echo htmlspecialchars($project['title']); ?></td>
                                <td>
                                    <?php
                                    $badgeClass = 'badge-ghost';
                                    switch($project['status']) {
                                        case 'active': $badgeClass = 'badge-success'; break;
                                        case 'completed': $badgeClass = 'badge-primary'; break;
                                        case 'on_hold': $badgeClass = 'badge-warning'; break;
                                        case 'planned': $badgeClass = 'badge-info'; break;
                                    }
                                    ?>
                                    <div class="badge <?php echo $badgeClass; ?> badge-sm font-bold p-3"><?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?></div>
                                </td>
                                <td>
                                    <?php if($project['start_date']): ?>
                                        <div class="text-xs opacity-70">Start: <?php echo date('M d, Y', strtotime($project['start_date'])); ?></div>
                                    <?php endif; ?>
                                    <?php if($project['end_date']): ?>
                                        <div class="text-xs opacity-70">End: <?php echo date('M d, Y', strtotime($project['end_date'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><div class="text-xs opacity-70"><?php echo date('M d, Y', strtotime($project['created_at'])); ?></div></td>
                                <td class="text-right">
                                    <div class="join">
                                        <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn btn-ghost btn-xs join-item text-primary">View</a>
                                        <?php if ($role === 'admin'): ?>
                                        <a href="delete_project.php?id=<?php echo $project['id']; ?>" class="btn btn-ghost btn-xs join-item text-error" onclick="return confirm('Are you sure?');">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="flex flex-col items-center gap-4">
                    <i class="bi bi-folder2-open text-6xl opacity-20"></i>
                    <p class="text-xl font-medium opacity-50">No projects found.</p>
                    <?php if ($role === 'admin'): ?>
                    <a href="create_project.php" class="btn btn-primary">Create Project</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
