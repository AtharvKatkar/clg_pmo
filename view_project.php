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

<div class="text-sm breadcrumbs mb-6">
    <ul>
        <li><a href="dashboard.php" class="text-primary font-medium">Dashboard</a></li>
        <li><?php echo htmlspecialchars($project['title']); ?></li>
    </ul>
</div>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-4xl font-black mb-1"><?php echo htmlspecialchars($project['title']); ?></h1>
        <p class="text-base-content/70 italic"><?php echo htmlspecialchars($project['description']); ?></p>
    </div>
    <?php if ($role === 'admin'): ?>
    <div>
        <a href="create_task.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary shadow-lg">
            <i class="bi bi-check2-square text-lg"></i>
            Create New Task
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Project Stats -->
<div class="stats shadow bg-base-100 w-full mb-8">
    <div class="stat">
        <div class="stat-figure text-primary">
            <i class="bi bi-clipboard-data text-3xl"></i>
        </div>
        <div class="stat-title">Total Tasks</div>
        <div class="stat-value text-primary"><?php echo $stats['total']; ?></div>
        <div class="stat-desc"><?php echo $role === 'admin' ? 'Total created' : 'Assigned to me'; ?></div>
    </div>
    <div class="stat">
        <div class="stat-figure text-secondary">
            <i class="bi bi-clock-history text-3xl"></i>
        </div>
        <div class="stat-title">To Do</div>
        <div class="stat-value text-secondary"><?php echo $stats['todo']; ?></div>
    </div>
    <div class="stat">
        <div class="stat-figure text-info">
            <i class="bi bi-gear-wide-connected text-3xl"></i>
        </div>
        <div class="stat-title">In Progress</div>
        <div class="stat-value text-info"><?php echo $stats['in_progress']; ?></div>
    </div>
    <div class="stat">
        <div class="stat-figure text-success">
            <i class="bi bi-check2-all text-3xl"></i>
        </div>
        <div class="stat-title">Completed</div>
        <div class="stat-value text-success"><?php echo $stats['done']; ?></div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card bg-base-100 shadow mb-8">
    <div class="card-body py-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
            <span class="font-bold">Filter By:</span>
            
            <select name="status" class="select select-bordered select-sm w-full max-w-xs" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="todo" <?php echo $filter_status === 'todo' ? 'selected' : ''; ?>>To Do</option>
                <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="review" <?php echo $filter_status === 'review' ? 'selected' : ''; ?>>Review</option>
                <option value="done" <?php echo $filter_status === 'done' ? 'selected' : ''; ?>>Done</option>
            </select>

            <select name="priority" class="select select-bordered select-sm w-full max-w-xs" onchange="this.form.submit()">
                <option value="">All Priorities</option>
                <option value="low" <?php echo $filter_priority === 'low' ? 'selected' : ''; ?>>Low</option>
                <option value="medium" <?php echo $filter_priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>High</option>
                <option value="critical" <?php echo $filter_priority === 'critical' ? 'selected' : ''; ?>>Critical</option>
            </select>

            <div class="ml-auto">
                 <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-ghost">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Tasks Table -->
<div class="card bg-base-100 shadow-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table table-zebra w-full">
            <thead class="bg-base-200">
                <tr>
                    <th><a href="<?php echo getSortLink('title', $sort_by, $sort_order); ?>" class="text-base-content no-underline hover:text-primary transition-colors">Task Title <?php echo $sort_by == 'title' ? ($sort_order == 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th>Assignees</th>
                    <th><a href="<?php echo getSortLink('status', $sort_by, $sort_order); ?>" class="text-base-content no-underline hover:text-primary transition-colors">Status <?php echo $sort_by == 'status' ? ($sort_order == 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th><a href="<?php echo getSortLink('priority', $sort_by, $sort_order); ?>" class="text-base-content no-underline hover:text-primary transition-colors">Priority <?php echo $sort_by == 'priority' ? ($sort_order == 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th><a href="<?php echo getSortLink('due_date', $sort_by, $sort_order); ?>" class="text-base-content no-underline hover:text-primary transition-colors">Due Date <?php echo $sort_by == 'due_date' ? ($sort_order == 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($tasks) > 0): ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr class="hover">
                            <td class="font-bold text-primary"><?php echo htmlspecialchars($task['title']); ?></td>
                            <td>
                                <?php if ($task['assignees']): ?>
                                    <div class="text-xs opacity-70"><?php echo htmlspecialchars($task['assignees']); ?></div>
                                <?php else: ?>
                                    <div class="badge badge-ghost badge-sm opacity-50">Unassigned</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = 'badge-ghost';
                                switch($task['status']) {
                                    case 'todo': $statusClass = 'badge-ghost'; break;
                                    case 'in_progress': $statusClass = 'badge-info'; break;
                                    case 'review': $statusClass = 'badge-warning'; break;
                                    case 'done': $statusClass = 'badge-success'; break;
                                }
                                ?>
                                <div class="badge <?php echo $statusClass; ?> badge-sm font-bold p-3"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></div>
                            </td>
                            <td>
                                <?php
                                $prioClass = 'badge-ghost';
                                switch($task['priority']) {
                                    case 'low': $prioClass = 'badge-ghost'; break;
                                    case 'medium': $prioClass = 'badge-info'; break;
                                    case 'high': $prioClass = 'badge-warning'; break;
                                    case 'critical': $prioClass = 'badge-error'; break;
                                }
                                ?>
                                <div class="badge <?php echo $prioClass; ?> badge-sm font-bold p-3"><?php echo ucfirst($task['priority']); ?></div>
                            </td>
                            <td>
                                <?php 
                                if ($task['due_date']) {
                                    $due = strtotime($task['due_date']);
                                    $isOverdue = $due < time() && $task['status'] !== 'done';
                                    echo '<div class="text-sm ' . ($isOverdue ? 'text-error font-bold' : '') . '">' . date('M d, Y', $due) . '</div>';
                                } else {
                                    echo '<div class="opacity-30">-</div>';
                                }
                                ?>
                            </td>
                            <td class="text-right">
                                <div class="join">
                                    <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-ghost btn-xs join-item text-info" title="View Details"><i class="bi bi-eye"></i></a>
                                    <?php if ($role === 'admin'): ?>
                                        <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-ghost btn-xs join-item text-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="delete_task.php?id=<?php echo $task['id']; ?>" class="btn btn-ghost btn-xs join-item text-error" onclick="return confirm('Are you sure you want to delete this task?');" title="Delete"><i class="bi bi-trash"></i></a>
                                    <?php else: ?>
                                        <a href="update_task_status.php?id=<?php echo $task['id']; ?>" class="btn btn-primary btn-xs join-item shadow-sm">Status</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="flex flex-col items-center gap-4">
                                <i class="bi bi-clipboard-check text-6xl opacity-20"></i>
                                <p class="text-xl font-medium opacity-50">No tasks found.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
