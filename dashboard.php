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

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-0">Dashboard</h1>
                <p class="text-secondary">Welcome back, <?php echo htmlspecialchars($username); ?>!</p>
            </div>
            <?php if ($role === 'admin'): ?>
                <a href="create_project.php" class="btn btn-success">
                    <i class="bi bi-plus-lg me-2"></i>Create New Project
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <?php if ($role === 'admin'): ?>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Total Projects</h5>
                <h2 class="display-4 fw-bold mb-0"><?php echo $total_projects; ?></h2>
                <p class="card-text text-white-50">All time projects</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Active Projects</h5>
                <h2 class="display-4 fw-bold mb-0"><?php echo $active_projects; ?></h2>
                <p class="card-text text-white-50">Currently in progress</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Pending</h5>
                <h2 class="display-4 fw-bold mb-0"><?php echo $total_projects - $active_projects; ?></h2>
                <p class="card-text text-white-50">Planned or on hold</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Team Projects</h5>
                <h2 class="display-4 fw-bold mb-0"><?php echo count($projects); ?></h2>
                <p class="card-text text-white-50">Projects created by Admin</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-warning text-dark h-100">
            <div class="card-body">
                <h5 class="card-title">My Pending Tasks</h5>
                <h2 class="display-4 fw-bold mb-0"><?php echo $total_projects; ?></h2>
                <p class="card-text text-dark-50">To Do / In Progress</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">My Completed Tasks</h5>
                <h2 class="display-4 fw-bold mb-0"><?php echo $active_projects; ?></h2>
                <p class="card-text text-white-50">Tasks marked as Done</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Projects Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><?php echo $role === 'admin' ? 'My Projects' : 'My Team\'s Projects'; ?></h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($projects) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Project Name</th>
                                    <th>Status</th>
                                    <th>Timeline</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td class="fw-medium"><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = 'secondary';
                                            switch($project['status']) {
                                                case 'active': $badgeClass = 'success'; break;
                                                case 'completed': $badgeClass = 'primary'; break;
                                                case 'on_hold': $badgeClass = 'warning'; break;
                                                case 'planned': $badgeClass = 'info'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?></span>
                                        </td>
                                        <td>
                                            <?php if($project['start_date']): ?>
                                                <small class="text-muted d-block">Start: <?php echo date('M d, Y', strtotime($project['start_date'])); ?></small>
                                            <?php endif; ?>
                                            <?php if($project['end_date']): ?>
                                                <small class="text-muted d-block">End: <?php echo date('M d, Y', strtotime($project['end_date'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo date('M d, Y', strtotime($project['created_at'])); ?></small></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary">View</a>
                                                <?php if ($role === 'admin'): ?>
                                                <a href="delete_project.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-folder2-open display-1 text-secondary"></i>
                        <p class="mt-3 text-secondary">No projects found.</p>
                        <?php if ($role === 'admin'): ?>
                        <a href="create_project.php" class="btn btn-primary mt-2">Create Project</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
