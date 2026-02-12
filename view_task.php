<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

// Enable full width layout and hide default navbar
$full_width_layout = true;
$hide_navbar = true;
include 'includes/header.php';

$task_id = $_GET['id'] ?? null;
$task = null;

if ($task_id) {
    // Fetch Task Details
    $stmt = $pdo->prepare("SELECT t.*, p.title as project_title, p.id as project_id,
                          GROUP_CONCAT(u.username SEPARATOR ', ') as assignees 
                          FROM tasks t 
                          JOIN projects p ON t.project_id = p.id
                          LEFT JOIN task_assignments ta ON t.id = ta.task_id 
                          LEFT JOIN users u ON ta.user_id = u.id 
                          WHERE t.id = ?
                          GROUP BY t.id");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
}

if (!$task) {
    echo '<div class="alert alert-error max-w-lg mx-auto mt-10 shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>Task not found or invalid ID.</span>
          </div>';
    include 'includes/footer.php';
    exit();
}

// Fetch Comments
$stmt = $pdo->prepare("SELECT c.*, u.username 
                      FROM comments c 
                      JOIN users u ON c.user_id = u.id 
                      WHERE c.task_id = ? 
                      ORDER BY c.created_at ASC");
$stmt->execute([$task_id]);
$comments = $stmt->fetchAll();

// Fetch Reactions
$reactions_data = [];
if (!empty($comments)) {
    $comment_ids = array_column($comments, 'id');
    $placeholders = implode(',', array_fill(0, count($comment_ids), '?'));
    $stmt = $pdo->prepare("SELECT r.*, u.username 
                          FROM comment_reactions r 
                          JOIN users u ON r.user_id = u.id 
                          WHERE r.comment_id IN ($placeholders)");
    $stmt->execute($comment_ids);
    $all_reactions = $stmt->fetchAll();
    
    foreach ($all_reactions as $row) {
        $reactions_data[$row['comment_id']][$row['reaction_type']][] = $row;
    }
}

$predefined_emojis = ['👍', '❤️', '😄', '🎉', '🚀'];
$user_id = $_SESSION['user_id'];
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
                <li><a href="view_project.php?id=<?php echo $task['project_id']; ?>" class="text-primary font-medium"><?php echo htmlspecialchars($task['project_title']); ?></a></li>
                <li><?php echo htmlspecialchars($task['title']); ?></li>
            </ul>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Task Info Sidebar -->
            <div class="w-full lg:w-1/3 order-2 lg:order-1">
                <div class="card bg-base-100 shadow-xl border border-base-200 top-4 sticky">
                    <div class="card-body">
                        <h2 class="card-title text-xl font-bold mb-4 border-b border-base-200 pb-2">Task Details</h2>
                        
                        <div class="space-y-6">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider opacity-50 block mb-1">Status</span>
                                <?php
                                $statusClass = 'badge-ghost';
                                switch($task['status']) {
                                    case 'todo': $statusClass = 'badge-ghost'; break;
                                    case 'in_progress': $statusClass = 'badge-info'; break;
                                    case 'review': $statusClass = 'badge-warning'; break;
                                    case 'done': $statusClass = 'badge-success'; break;
                                }
                                ?>
                                <div class="badge <?php echo $statusClass; ?> font-bold p-3"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></div>
                            </div>

                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider opacity-50 block mb-1">Priority</span>
                                <?php
                                $prioClass = 'badge-ghost';
                                switch($task['priority']) {
                                    case 'low': $prioClass = 'badge-ghost'; break;
                                    case 'medium': $prioClass = 'badge-info'; break;
                                    case 'high': $prioClass = 'badge-warning'; break;
                                    case 'critical': $prioClass = 'badge-error'; break;
                                }
                                ?>
                                <div class="badge <?php echo $prioClass; ?> font-bold p-3"><?php echo ucfirst($task['priority']); ?></div>
                            </div>

                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider opacity-50 block mb-1">Due Date</span>
                                <p class="font-bold">
                                    <?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : '<span class="opacity-30">No date set</span>'; ?>
                                </p>
                            </div>

                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider opacity-50 block mb-1">Assignees</span>
                                <p class="font-bold">
                                    <?php echo $task['assignees'] ? htmlspecialchars($task['assignees']) : '<span class="opacity-30">Unassigned</span>'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline & Comments -->
            <div class="w-full lg:w-2/3 order-1 lg:order-2">
                <div class="card bg-base-100 shadow-xl p-8 border border-base-200">
                    <h1 class="text-4xl font-black mb-2"><?php echo htmlspecialchars($task['title']); ?></h1>
                    <div class="text-base-content/70 text-lg mb-8 leading-relaxed"><?php echo nl2br(htmlspecialchars($task['description'])); ?></div>

                    <div class="divider">DISCUSSION</div>
                    
                    <!-- Comment Timeline -->
                    <div class="space-y-6 mt-6">
                        <?php if (count($comments) > 0): ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="chat chat-start">
                                    <div class="chat-header mb-1">
                                        <span class="font-bold"><?php echo htmlspecialchars($comment['username']); ?></span>
                                        <time class="text-xs opacity-50 ml-2"><?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?></time>
                                    </div>
                                    <div class="chat-bubble bg-base-200 text-base-content shadow-sm border border-base-300">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                    <div class="chat-footer mt-2 flex flex-wrap gap-2">
                                        <?php foreach ($predefined_emojis as $emoji): ?>
                                            <?php 
                                            $reacted_users = $reactions_data[$comment['id']][$emoji] ?? [];
                                            $count = count($reacted_users);
                                            $user_reacted = false;
                                            $user_list = [];
                                            foreach ($reacted_users as $r) {
                                                if ($r['user_id'] == $user_id) $user_reacted = true;
                                                $user_list[] = $r['username'];
                                            }
                                            $tooltip = !empty($user_list) ? implode(', ', $user_list) : '';
                                            ?>
                                            <button class="btn btn-xs btn-circle <?php echo $user_reacted ? 'btn-primary shadow-md' : 'btn-ghost bg-base-200'; ?> hover:scale-110 transition-transform" 
                                                    onclick="toggleReaction(<?php echo $comment['id']; ?>, '<?php echo $emoji; ?>')"
                                                    title="<?php echo htmlspecialchars($tooltip); ?>">
                                                <span><?php echo $emoji; ?></span>
                                                <?php if ($count > 0): ?>
                                                    <span class="text-[0.6rem] ml-1 font-black"><?php echo $count; ?></span>
                                                <?php endif; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-12 opacity-30">
                                <i class="bi bi-chat-dots text-6xl"></i>
                                <p class="mt-4 text-xl italic">No comments yet. Start the conversation!</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- New Comment Form -->
                    <div class="mt-12 pt-8 border-t border-base-200">
                        <form action="add_comment.php" method="POST" class="space-y-4">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-lg">Post a Comment</span></label>
                                <textarea class="textarea textarea-bordered h-32 text-lg bg-base-200/50" name="comment" placeholder="Write your update here..." required></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="btn btn-primary px-8 font-black shadow-lg">Post Comment</button>
                            </div>
                        </form>
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

<style>
    .reaction-btn {
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    .reaction-btn:hover {
        transform: scale(1.1);
        background: #e9ecef;
    }
    .reaction-btn.btn-primary:hover {
        background: #0d6efd;
    }
    .emoji {
        line-height: 1;
    }
</style>

<script>
function toggleReaction(commentId, emoji) {
    const formData = new FormData();
    formData.append('comment_id', commentId);
    formData.append('reaction_type', emoji);

    fetch('reaction_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload(); // Simple reload for now to reflect state
        } else {
            alert(data.message || 'Something went wrong');
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php include 'includes/footer.php'; ?>
