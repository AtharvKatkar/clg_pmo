<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
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

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Team</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="fw-bold">Manage Your Team</h1>
            <a href="create_member.php" class="btn btn-primary">
                <i class="bi bi-person-plus me-2"></i>Create New Member
            </a>
        </div>
        <p class="text-secondary">View and manage the team members you have added.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow p-4">
            <h3 class="fw-bold mb-4">Team Members</h3>
            <?php if (count($members) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Joined Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?php echo $member['id']; ?></td>
                                    <td><?php echo htmlspecialchars($member['username']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info">View</button>
                                        <!-- Placeholder for future delete/edit functionality -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-people text-secondary display-1"></i>
                    <p class="mt-3 text-secondary">No members created yet. Click "Create New Member" to add someone!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
