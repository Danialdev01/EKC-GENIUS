<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
$authUser = requireAuth('admin');
$pageTitle = 'Admins';
$cssDepth = '../../public/css';
$location_index = '../..';

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_admin'])) {
        $admin_id     = $_POST['admin_id'] ?? '';
        $admin_name   = trim($_POST['admin_name'] ?? '');
        $admin_email  = trim($_POST['admin_email'] ?? '');
        $admin_pass   = $_POST['admin_password'] ?? '';
        $admin_pass2  = $_POST['admin_password_confirm'] ?? '';

        if ($admin_name === '' || $admin_email === '') {
            $message = 'Name and email are required.';
            $messageType = 'error';
        } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        } else {
            if ($admin_id === '') {
                if ($admin_pass === '' || strlen($admin_pass) < 8) {
                    $message = 'Password is required and must be at least 8 characters.';
                    $messageType = 'error';
                } elseif ($admin_pass !== $admin_pass2) {
                    $message = 'Passwords do not match.';
                    $messageType = 'error';
                } else {
                    $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (admin_name, admin_email, admin_hash_password, admin_status, admin_created_at, admin_updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())");
                    $stmt->execute([$admin_name, $admin_email, $hash]);
                    $message = 'Admin added successfully!';
                }
            } else {
                if ($admin_pass !== '' && ($admin_pass !== $admin_pass2)) {
                    $message = 'New passwords do not match.';
                    $messageType = 'error';
                } else {
                    if ($admin_pass !== '') {
                        if (strlen($admin_pass) < 8) {
                            $message = 'New password must be at least 8 characters.';
                            $messageType = 'error';
                        } else {
                            $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE admins SET admin_name = ?, admin_email = ?, admin_hash_password = ?, admin_updated_at = NOW() WHERE admin_id = ?");
                            $stmt->execute([$admin_name, $admin_email, $hash, (int)$admin_id]);
                            $message = 'Admin updated successfully!';
                        }
                    } else {
                        $stmt = $pdo->prepare("UPDATE admins SET admin_name = ?, admin_email = ?, admin_updated_at = NOW() WHERE admin_id = ?");
                        $stmt->execute([$admin_name, $admin_email, (int)$admin_id]);
                        $message = 'Admin updated successfully!';
                    }
                }
            }
        }
    }

    if (isset($_POST['delete_admin'])) {
        $admin_id = (int)($_POST['admin_id'] ?? 0);
        if ($admin_id === (int)$authUser['id']) {
            $message = 'You cannot delete the admin account you are currently signed in as.';
            $messageType = 'error';
        } elseif ($admin_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE admin_status = 1");
            $stmt->execute();
            if ((int)$stmt->fetchColumn() <= 1) {
                $message = 'Cannot delete the last active admin.';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET admin_status = 0 WHERE admin_id = ?");
                $stmt->execute([$admin_id]);
                $message = 'Admin removed successfully!';
            }
        }
    }
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE admin_status = 1");
$totalAdmins = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalAdmins / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT admin_id, admin_name, admin_email, admin_status, admin_created_at, admin_updated_at FROM admins WHERE admin_status = 1 ORDER BY admin_name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT admin_id, admin_name, admin_email, admin_created_at FROM admins WHERE admin_status = 1 ORDER BY admin_created_at DESC LIMIT 5");
$recentAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../../components/teacher/header.php'; ?>

<?php include '../../components/sidebar.php'; ?>

<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Admins</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
        <button onclick="openAddModal()" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-inter text-sm font-medium px-4 py-2 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Admin
        </button>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        <?php if ($message): ?>
        <div class="<?= $messageType === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-700' ?> px-4 py-3 rounded-xl text-sm">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-2xl">🛡️</div>
                    <div>
                        <p class="text-sm text-slate-500">Total Admins</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800"><?= $totalAdmins ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-2xl">✅</div>
                    <div>
                        <p class="text-sm text-slate-500">Active Admins</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800"><?= $totalAdmins ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-2xl">🕒</div>
                    <div>
                        <p class="text-sm text-slate-500">Recent Sign-ins (90 days)</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800">
                            <?php
                            $ninetyDaysAgo = date('Y-m-d H:i:s', strtotime('-90 days'));
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE admin_status = 1 AND admin_updated_at >= ?");
                            $stmt->execute([$ninetyDaysAgo]);
                            echo (int)$stmt->fetchColumn();
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admins Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-poppins text-lg font-semibold text-slate-800">All Admins</h2>
                <p class="text-xs text-slate-400 mt-0.5">Manage administrator accounts</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Admin</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Email</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Created</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400 text-sm">No admins found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($admins as $admin):
                            $isSelf = ((int)$admin['admin_id'] === (int)$authUser['id']);
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                                        <?= strtoupper(substr($admin['admin_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                                            <?= htmlspecialchars($admin['admin_name']) ?>
                                            <?php if ($isSelf): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">You</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-slate-400">ID: <?= (int)$admin['admin_id'] ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600"><?= htmlspecialchars($admin['admin_email']) ?></td>
                            <td class="px-4 py-4 text-sm text-slate-500 whitespace-nowrap"><?= date('M d, Y', strtotime($admin['admin_created_at'])) ?></td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-1">
                                    <button onclick='openEditModal(<?= (int)$admin['admin_id'] ?>, <?= json_encode($admin['admin_name']) ?>, <?= json_encode($admin['admin_email']) ?>)' class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <?php if (!$isSelf): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this admin?');">
                                        <input type="hidden" name="admin_id" value="<?= (int)$admin['admin_id'] ?>">
                                        <button type="submit" name="delete_admin" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="p-2 text-slate-300" title="You cannot delete your own account">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between">
                <p class="text-xs text-slate-400">Page <?= $page ?> of <?= $totalPages ?></p>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Previous</a>
                    <?php endif; ?>
                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <a href="?page=<?= $p ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $p === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 hover:bg-slate-50' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Add/Edit Modal -->
<div id="adminModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
        <h3 id="modalTitle" class="font-poppins text-lg font-semibold text-slate-800 mb-4">Add Admin</h3>
        <form method="POST" id="adminForm">
            <input type="hidden" name="admin_id" id="adminId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Name *</label>
                    <input type="text" name="admin_name" id="adminName" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter admin name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email *</label>
                    <input type="email" name="admin_email" id="adminEmail" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="admin@example.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Password
                        <span id="passwordHint" class="text-xs font-normal text-slate-400">(required, min 8 chars)</span>
                    </label>
                    <input type="password" name="admin_password" id="adminPassword" minlength="8" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Confirm Password</label>
                    <input type="password" name="admin_password_confirm" id="adminPasswordConfirm" minlength="8" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="••••••••">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" name="save_admin" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-4 py-2.5 rounded-xl transition-colors">Save</button>
                <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Admin';
    var form = document.getElementById('adminForm');
    form.reset();
    document.getElementById('adminId').value = '';
    document.getElementById('adminPassword').required = true;
    document.getElementById('adminPasswordConfirm').required = true;
    document.getElementById('passwordHint').textContent = '(required, min 8 chars)';
    document.getElementById('adminModal').classList.remove('hidden');
    document.getElementById('adminModal').classList.add('flex');
}

function openEditModal(id, name, email) {
    document.getElementById('modalTitle').textContent = 'Edit Admin';
    document.getElementById('adminId').value = id;
    document.getElementById('adminName').value = name;
    document.getElementById('adminEmail').value = email;
    document.getElementById('adminPassword').value = '';
    document.getElementById('adminPasswordConfirm').value = '';
    document.getElementById('adminPassword').required = false;
    document.getElementById('adminPasswordConfirm').required = false;
    document.getElementById('passwordHint').textContent = '(leave blank to keep current)';
    document.getElementById('adminModal').classList.remove('hidden');
    document.getElementById('adminModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('adminModal').classList.add('hidden');
    document.getElementById('adminModal').classList.remove('flex');
}

document.getElementById('adminModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
