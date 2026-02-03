<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
include 'includes/header.php';

$project_id = $_GET['id'] ?? null;
$project = null;

if ($project_id) {
    // Fetch Project Details
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
}

if (!$project) {
    echo '<div class="alert alert-danger container mt-4">Project not found or invalid ID.</div>';
    include 'includes/footer.php';
    exit();
}

// Stats Queries
$stats = [
    'total' => 0,
    'todo' => 0,
    'in_progress' => 0,
    'review' => 0,
    'done' => 0
];

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Stats Logic tailored for roles
try {
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE project_id = ? GROUP BY status");
        $stmt->execute([$project_id]);
    } else {
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks t 
                               JOIN task_assignments ta ON t.id = ta.task_id 
                               WHERE t.project_id = ? AND ta.user_id = ? 
                               GROUP BY status");
        $stmt->execute([$project_id, $user_id]);
    }
    
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $stats[$row['status']] = $row['count'];
        $stats['total'] += $row['count'];
    }
} catch (PDOException $e) { /* Handle missing table gracefully */ }

// Task Filtering
$filter_status = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';

// Default sort: Admin -> Created At, Member -> Due Date
$default_sort = ($role === 'admin') ? 'created_at' : 'due_date';
$sort_by = $_GET['sort'] ?? $default_sort;
$sort_order = $_GET['order'] ?? ($sort_by === 'due_date' ? 'ASC' : 'DESC');

$query = "SELECT t.*, 
          GROUP_CONCAT(u.username SEPARATOR ', ') as assignees 
          FROM tasks t 
          LEFT JOIN task_assignments ta ON t.id = ta.task_id 
          LEFT JOIN users u ON ta.user_id = u.id 
          WHERE t.project_id = ?";
$params = [$project_id];

// For members, restrict to assigned tasks
if ($role !== 'admin') {
    // We need to filter by the current user's assignment.
    // Since we are already joining task_assignments (ta) for the GROUP_CONCAT, 
    // we need to be careful not to filter out OTHER assignees from the display list.
    // So we use a subquery or separate check.
    // Simpler approach: Add a WHERE clause checking existence.
    $query .= " AND EXISTS (SELECT 1 FROM task_assignments ta2 WHERE ta2.task_id = t.id AND ta2.user_id = ?)";
    $params[] = $user_id;
}

if ($filter_status) {
    $query .= " AND t.status = ?";
    $params[] = $filter_status;
}
if ($filter_priority) {
    $query .= " AND t.priority = ?";
    $params[] = $filter_priority;
}

// Whitelist sorting columns to prevent SQL injection
$allowed_sorts = ['title', 'status', 'priority', 'due_date', 'created_at'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'created_at';
$query .= " GROUP BY t.id ORDER BY t.$sort_by $sort_order";

$tasks = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) { /* Table might not exist yet */ }

// Helper for sort links
function getSortLink($col, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    return "?id=" . $_GET['id'] . "&sort=$col&order=$newOrder" . 
           (isset($_GET['status']) ? "&status=".$_GET['status'] : "") .
           (isset($_GET['priority']) ? "&priority=".$_GET['priority'] : "");
}
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($project['title']); ?></li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-0"><?php echo htmlspecialchars($project['title']); ?></h1>
                <p class="text-secondary mb-0"><?php echo htmlspecialchars($project['description']); ?></p>
            </div>
            <?php if ($role === 'admin'): ?>
            <div>
                <a href="create_task.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-check2-square me-2"></i>Create New Task
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Project Stats -->
<div class="row mb-4">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white text-center p-3">
            <h3 class="fw-bold mb-0"><?php echo $stats['total']; ?></h3>
            <small><?php echo $role === 'admin' ? 'Total Tasks' : 'My Tasks'; ?></small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 bg-secondary text-white text-center p-3">
            <h3 class="fw-bold mb-0"><?php echo $stats['todo']; ?></h3>
            <small>To Do</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 bg-info text-white text-center p-3">
            <h3 class="fw-bold mb-0"><?php echo $stats['in_progress']; ?></h3>
            <small>In Progress</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 bg-success text-white text-center p-3">
            <h3 class="fw-bold mb-0"><?php echo $stats['done']; ?></h3>
            <small>Completed</small>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card shadow mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-center">
            <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
            <div class="col-auto">
                <label class="fw-bold me-2">Filter By:</label>
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="todo" <?php echo $filter_status === 'todo' ? 'selected' : ''; ?>>To Do</option>
                    <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="review" <?php echo $filter_status === 'review' ? 'selected' : ''; ?>>Review</option>
                    <option value="done" <?php echo $filter_status === 'done' ? 'selected' : ''; ?>>Done</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <option value="low" <?php echo $filter_priority === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $filter_priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo $filter_priority === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            <div class="col-auto ms-auto">
                 <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Tasks Table -->
<div class="card shadow">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><a href="<?php echo getSortLink('title', $sort_by, $sort_order); ?>" class="text-dark text-decoration-none">Task Title <?php echo $sort_by == 'title' ? ($sort_order == 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th>Assignees</th>
                    <th><a href="<?php echo getSortLink('status', $sort_by, $sort_order); ?>" class="text-dark text-decoration-none">Status <?php echo $sort_by == 'status' ? ($sort_order == 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th><a href="<?php echo getSortLink('priority', $sort_by, $sort_order); ?>" class="text-dark text-decoration-none">Priority <?php echo $sort_by == 'priority' ? ($sort_order == 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th><a href="<?php echo getSortLink('due_date', $sort_by, $sort_order); ?>" class="text-dark text-decoration-none">Due Date <?php echo $sort_by == 'due_date' ? ($sort_order == 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($tasks) > 0): ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($task['title']); ?></td>
                            <td>
                                <?php if ($task['assignees']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($task['assignees']); ?></small>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = 'secondary';
                                switch($task['status']) {
                                    case 'todo': $statusClass = 'secondary'; break;
                                    case 'in_progress': $statusClass = 'primary'; break;
                                    case 'review': $statusClass = 'warning'; break;
                                    case 'done': $statusClass = 'success'; break;
                                }
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span>
                            </td>
                            <td>
                                <?php
                                $prioClass = 'secondary';
                                switch($task['priority']) {
                                    case 'low': $prioClass = 'secondary'; break;
                                    case 'medium': $prioClass = 'info'; break;
                                    case 'high': $prioClass = 'warning'; break;
                                    case 'critical': $prioClass = 'danger'; break;
                                }
                                ?>
                                <span class="badge bg-<?php echo $prioClass; ?>"><?php echo ucfirst($task['priority']); ?></span>
                            </td>
                            <td>
                                <?php 
                                if ($task['due_date']) {
                                    $due = strtotime($task['due_date']);
                                    $isOverdue = $due < time() && $task['status'] !== 'done';
                                    echo '<span class="' . ($isOverdue ? 'text-danger fw-bold' : '') . '">' . date('M d, Y', $due) . '</span>';
                                } else {
                                    echo '<span class="text-muted">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($role === 'admin'): ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-outline-secondary">Edit</a>
                                        <a href="delete_task.php?id=<?php echo $task['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this task?');">Delete</a>
                                    </div>
                                <?php else: ?>
                                    <a href="update_task_status.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">Update Status</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="bi bi-clipboard-check display-4 text-secondary"></i>
                            <p class="mt-3 text-seconday">No tasks found.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
