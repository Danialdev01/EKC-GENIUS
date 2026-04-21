<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
$authUser  = requireAuth('teacher');
$pageTitle = 'Activities';
$cssDepth  = '../../public/css';
$location_index = '../..';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_activity'])) {
        $activity_name = trim($_POST['activity_name'] ?? '');
        $activity_description = trim($_POST['activity_description'] ?? '');
        $activity_type = trim($_POST['activity_type'] ?? '');
        $activity_active_at = $_POST['activity_active_at'] ?? null;
        
        if (!empty($activity_name) && !empty($activity_description)) {
            if (isset($_POST['activity_id']) && !empty($_POST['activity_id'])) {
                $stmt = $pdo->prepare("UPDATE activites SET activity_name = ?, activity_description = ?, activity_type = ?, activity_active_at = ?, activity_updated_at = NOW() WHERE activity_id = ?");
                $stmt->execute([$activity_name, $activity_description, $activity_type, $activity_active_at, $_POST['activity_id']]);
                $message = 'Activity updated successfully!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO activites (activity_name, activity_description, activity_type, activity_active_at, activity_status, activity_created_at, activity_updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
                $stmt->execute([$activity_name, $activity_description, $activity_type, $activity_active_at]);
                $message = 'Activity created successfully!';
            }
        }
    }
    
    if (isset($_POST['delete_activity'])) {
        $activity_id = $_POST['activity_id'] ?? 0;
        if ($activity_id) {
            $stmt = $pdo->prepare("UPDATE activites SET activity_status = 0 WHERE activity_id = ?");
            $stmt->execute([$activity_id]);
            $message = 'Activity deleted successfully!';
        }
    }
}

$search = trim($_GET['search'] ?? '');
$params = [];
$where = "a.activity_status = 1";

if ($search) {
    $where .= " AND a.activity_name LIKE ?";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("SELECT a.*, COUNT(ass.student_id) as participant_count FROM activites a LEFT JOIN assignments ass ON a.activity_id = ass.activity_id AND ass.assignment_status = 1 WHERE $where GROUP BY a.activity_id ORDER BY a.activity_created_at DESC");
$stmt->execute($params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../../components/teacher/header.php'; ?>

<?php include '../../components/teacher/sidebar.php'; ?>

<!-- Main Wrapper -->
<main class="lg:ml-64 min-h-screen flex flex-col">

    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleTeacherSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Activities</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-slate-500 hidden md:block"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        
        <?php if (isset($message)): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-4">
            <div class="w-full max-w-xs">
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Search activities...">
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors">Search</button>
                </form>
            </div>
            <div class="p-2">
                <a href="add.php" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-5 py-2.5 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Activity
                </a>

            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Activity Name</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Date</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Participants</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                No activities found. Click "Add Activity" to create one.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($activities as $a): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-medium text-slate-800"><?= htmlspecialchars($a['activity_name']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-600 line-clamp-2"><?= htmlspecialchars($a['activity_description']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                    <?= htmlspecialchars($a['activity_type'] ?? 'General') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?= $a['activity_active_at'] ? date('d M Y', strtotime($a['activity_active_at'])) : '—' ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                    <?= (int)$a['participant_count'] ?> Participants
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="edit.php?id=<?= (int)$a['activity_id'] ?>" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this activity?')">
                                        <input type="hidden" name="activity_id" value="<?= $a['activity_id'] ?>">
                                        <button type="submit" name="delete_activity" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

</body>
</html>