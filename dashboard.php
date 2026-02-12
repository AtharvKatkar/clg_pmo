<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

// Enable full width layout and hide default navbar
$full_width_layout = true;
$hide_navbar = true;
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

function getInitials($name) {
    if (empty($name)) return 'AU';
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

// --- DATA FETCHING ---
// (Same data fetching logic as before)
$undone_tasks_count = 0;
$in_progress_tasks_count = 0;
$total_tasks_assigned = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_assignments ta JOIN tasks t ON ta.task_id = t.id WHERE ta.user_id = ? AND t.status != 'done'");
    $stmt->execute([$user_id]);
    $undone_tasks_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_assignments ta JOIN tasks t ON ta.task_id = t.id WHERE ta.user_id = ? AND t.status = 'in_progress'");
    $stmt->execute([$user_id]);
    $in_progress_tasks_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_assignments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_tasks_assigned = $stmt->fetchColumn();
} catch (PDOException $e) {}

$my_tasks = [];
try {
    $stmt = $pdo->prepare("SELECT t.*, p.title as project_title FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id JOIN projects p ON t.project_id = p.id WHERE ta.user_id = ? ORDER BY t.due_date ASC LIMIT 10");
    $stmt->execute([$user_id]);
    $my_tasks = $stmt->fetchAll();
} catch (PDOException $e) {}

$active_projects_list = [];
try {
    $query = "SELECT * FROM projects WHERE status = 'active'";
    if ($role !== 'admin') {
        $stmtUser = $pdo->prepare("SELECT created_by FROM users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $u = $stmtUser->fetch();
        $admin_id = $u['created_by'] ?? 0;
        $query .= " AND created_by = $admin_id"; 
    } else {
        $query .= " AND created_by = $user_id";
    }
    $query .= " ORDER BY created_at DESC LIMIT 3";
    $stmt = $pdo->query($query);
    $active_projects_list = $stmt->fetchAll();
} catch (PDOException $e) {}

$team_members = [];
try {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users LIMIT 6");
    $stmt->execute();
    $team_members = $stmt->fetchAll();
} catch (PDOException $e) {}

$recent_comments = [];
try {
    $stmt = $pdo->query("SELECT c.comment, c.created_at, c.task_id, u.username, t.title as task_title FROM comments c JOIN users u ON c.user_id = u.id JOIN tasks t ON c.task_id = t.id ORDER BY c.created_at DESC LIMIT 5");
    $recent_comments = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<style>
    .dashboard-font { font-family: 'Inter', sans-serif; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<!-- DRAWER LAYOUT START -->
<div class="drawer lg:drawer-open dashboard-font text-base-content bg-base-100">
  <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
  
  <div class="drawer-content flex flex-col bg-base-100">
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
    <div class="p-4 lg:p-8 min-h-screen">
        
        <!-- HEADER ROW -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <p class="text-sm opacity-60">Today</p>
                <h2 class="text-2xl font-bold"><?php echo date('D, M d, Y'); ?></h2>
            </div>
            
            <div class="flex flex-1 w-full md:w-auto justify-end items-center gap-4">
                 <div class="form-control w-full max-w-xs hidden md:block">
                    <label class="input input-sm bg-base-200 border-none flex items-center gap-2 rounded-full text-sm">
                        <i class="bi bi-search opacity-50"></i>
                        <input type="text" class="grow placeholder-base-content/30" placeholder="Search.." />
                    </label>
                </div>
                
                <!-- Profile & Notifications -->
                <div class="flex items-center gap-3 bg-base-200 px-4 py-2 rounded-full">
                     <div class="avatar placeholder">
                         <div class="bg-primary text-primary-content rounded-full w-8">
                            <span class="text-xs"><?php echo getInitials($username); ?></span>
                        </div>
                    </div>
                    <div class="hidden sm:block text-right leading-tight">
                        <p class="font-bold text-xs"><?php echo htmlspecialchars($username); ?></p>
                        <p class="text-[10px] opacity-50 truncate w-24">User</p>
                    </div>
                    <button class="btn btn-ghost btn-circle btn-xs opacity-70 hover:opacity-100"><i class="bi bi-bell"></i></button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-8">
            <!-- CENTER COLUMN (Stats + Tasks) -->
            <div class="col-span-12 lg:col-span-8 flex flex-col gap-8">
                
                <!-- STATS CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Undone Tasks -->
                    <div class="card bg-base-200 shadow-xl image-full">
                        <figure><div class="w-full h-full bg-gradient-to-br from-base-300 to-base-100 opacity-50"></div></figure>
                        <div class="card-body relative overflow-hidden">
                             <div class="z-10 flex flex-col h-full justify-between">
                                <div>
                                    <h3 class="font-bold text-lg mb-1 text-base-content">You Have <?php echo $undone_tasks_count; ?> Undone Tasks</h3>
                                    <p class="text-sm opacity-60"><?php echo $in_progress_tasks_count; ?> Tasks are in progress</p>
                                </div>
                                <div class="mt-4">
                                    <button class="btn btn-primary btn-sm px-6 rounded-full font-bold shadow-lg">Check</button>
                                </div>
                            </div>
                             <div class="absolute right-0 bottom-0 w-32 h-32 opacity-5 pointer-events-none">
                                <i class="bi bi-list-task text-9xl text-base-content"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Progress / Sprint -->
                    <div class="card bg-primary text-primary-content shadow-xl">
                        <div class="card-body flex-row items-center justify-between">
                            <div class="z-10">
                                <h3 class="font-bold text-lg mb-1">Current Sprint</h3>
                                <div class="text-sm opacity-80 space-y-1 mt-2">
                                    <p><?php echo $total_tasks_assigned; ?> Tasks Assigned</p>
                                    <p><?php echo $in_progress_tasks_count; ?> In Progress</p>
                                </div>
                            </div>
                            <div class="z-10">
                                <div class="radial-progress text-primary-content font-bold text-xs" style="--value:70; --size:4.5rem; --thickness: 4px;">70%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TASK OVERVIEW -->
                <div class="flex flex-col gap-4">
                    <div class="flex justify-between items-center px-1">
                        <h3 class="font-bold text-xl">Task Overview</h3>
                        <div class="tabs tabs-boxed bg-base-200 p-1 rounded-full text-xs hidden sm:flex">
                            <a class="tab tab-active rounded-full bg-base-100 shadow-sm text-xs px-4">All</a>
                            <a class="tab text-xs px-4 opacity-70">To do</a>
                            <a class="tab text-xs px-4 opacity-70">In progress</a>
                        </div>
                    </div>

                    <div class="flex overflow-x-auto gap-4 pb-4 scrollbar-hide">
                        <?php if(empty($my_tasks)): ?>
                            <div class="w-full text-center py-8 opacity-50 bg-base-200 rounded-2xl">
                                <p>No tasks assigned yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($my_tasks as $task): ?>
                            <div class="card bg-base-200 w-[260px] min-w-[260px] shadow-lg hover:shadow-xl transition-all cursor-pointer group" onclick="window.location.href='view_task.php?id=<?php echo $task['id']; ?>'">
                                <div class="card-body p-5 gap-3">
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-bold text-sm line-clamp-2 leading-tight group-hover:text-primary transition-colors h-[2.5em]"><?php echo htmlspecialchars($task['title']); ?></h4>
                                        <button class="btn btn-ghost btn-xs btn-circle opacity-50"><i class="bi bi-three-dots-vertical"></i></button>
                                    </div>
                                    
                                    <div>
                                        <p class="text-[10px] opacity-50 mb-1">Project</p>
                                        <p class="text-xs font-semibold text-primary truncate"><?php echo htmlspecialchars($task['project_title']); ?></p>
                                    </div>

                                    <div class="mt-auto flex items-center justify-between">
                                        <span class="badge <?php echo match($task['status']) { 'todo'=>'badge-error', 'in_progress'=>'badge-warning', 'review'=>'badge-info', 'done'=>'badge-success', default=>'badge-ghost' }; ?> badge-sm text-[10px] font-bold py-3"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span>
                                        
                                        <div class="avatar placeholder">
                                            <div class="bg-neutral text-neutral-content rounded-full w-6 h-6">
                                                <span class="text-[10px]"><?php echo getInitials($username); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-4 text-[10px] opacity-40 pt-3 border-t border-base-content/10">
                                        <span class="flex items-center gap-1"><i class="bi bi-chat"></i> 2</span>
                                        <span class="flex items-center gap-1"><i class="bi bi-paperclip"></i> 5</span>
                                        <span class="ml-auto"><?php echo $task['due_date'] ? date('M d', strtotime($task['due_date'])) : '--'; ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ACTIVE PROJECTS (List View) -->
                <div>
                     <div class="flex justify-between items-center mb-4 px-1">
                        <h3 class="font-bold text-xl">My Active Projects</h3>
                        <a href="#" class="text-xs opacity-50 hover:text-primary">See All</a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach($active_projects_list as $proj): ?>
                        <div class="card md:card-side bg-base-200 shadow-md hover:shadow-lg transition-all cursor-pointer p-4" onclick="window.location.href='view_project.php?id=<?php echo $proj['id']; ?>'">
                            <div class="flex items-center gap-4 w-full">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    <?php echo substr($proj['title'], 0, 1); ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold truncate text-sm"><?php echo htmlspecialchars($proj['title']); ?></h4>
                                    <p class="text-[10px] opacity-50">Due: <?php echo $proj['end_date'] ? date('M d', strtotime($proj['end_date'])) : 'TBD'; ?></p>
                                </div>
                                <div class="radial-progress text-primary text-[8px]" style="--value:45; --size:2rem; --thickness: 2px;">45%</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN (Team + Comments) -->
            <div class="col-span-12 lg:col-span-4 flex flex-col gap-8 pl-0 lg:pl-6">
                
                <!-- Team Members Section -->
                <div class="card bg-base-200 shadow-xl">
                    <div class="card-body p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-md">Team Members</h3>
                            <button class="btn btn-ghost btn-xs btn-circle opacity-50"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($team_members as $member): ?>
                                <div class="avatar placeholder tooltip" data-tip="<?php echo htmlspecialchars($member['username']); ?>">
                                     <div class="bg-neutral text-neutral-content rounded-full w-10 ring ring-base-100 ring-offset-2 ring-offset-base-100">
                                        <span><?php echo getInitials($member['username']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button class="btn btn-circle btn-sm btn-outline border-dashed opacity-50 hover:opacity-100"><i class="bi bi-plus"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Latest Comments -->
                <div class="card bg-base-200 shadow-xl flex-1">
                    <div class="card-body p-6">
                         <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold text-md"><i class="bi bi-chat-dots-fill mr-2 text-primary"></i>Latest Comments</h3>
                        </div>
                        
                        <div class="flex flex-col gap-4">
                            <?php foreach($recent_comments as $comment): ?>
                            <a href="view_task.php?id=<?php echo $comment['task_id']; ?>" class="flex gap-3 items-start group hover:bg-base-300/50 p-2 -mx-2 rounded-xl transition-colors">
                                <div class="avatar placeholder mt-1">
                                     <div class="bg-neutral text-neutral-content rounded-full w-8 h-8">
                                        <span class="text-xs"><?php echo getInitials($comment['username']); ?></span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-baseline mb-0.5">
                                        <p class="font-bold text-xs truncate group-hover:text-primary transition-colors"><?php echo htmlspecialchars($comment['username']); ?></p>
                                        <p class="text-[10px] opacity-30"><?php echo date('M d', strtotime($comment['created_at'])); ?></p>
                                    </div>
                                    <p class="text-[10px] opacity-50 truncate mb-1" title="<?php echo htmlspecialchars($comment['task_title']); ?>">in <span class="text-primary"><?php echo htmlspecialchars($comment['task_title']); ?></span></p>
                                    <p class="text-xs opacity-80 line-clamp-2 leading-relaxed bg-base-100 p-2 rounded-lg rounded-tl-none"><?php echo htmlspecialchars($comment['comment']); ?></p>
                                </div>
                            </a>
                            <?php endforeach; ?>
                            
                            <?php if(empty($recent_comments)): ?>
                                <p class="text-xs opacity-50 text-center py-4">No recent comments.</p>
                            <?php endif; ?>
                        </div>
                    </div>
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
