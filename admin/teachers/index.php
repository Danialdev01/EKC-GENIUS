<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
$authUser = requireAuth('admin');
$pageTitle = 'Teachers';
$cssDepth = '../../public/css';
$location_index = '../..';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_teacher'])) {
        $teacher_name = trim($_POST['teacher_name'] ?? '');
        $teacher_email = trim($_POST['teacher_email'] ?? '');
        $teacher_phone_number = trim($_POST['teacher_phone_number'] ?? '');
        $teacher_specialization = trim($_POST['teacher_specialization'] ?? '');
        $teacher_notes = trim($_POST['teacher_notes'] ?? '');
        
        if (!empty($teacher_name)) {
            $stmt = $pdo->prepare("INSERT INTO teachers (teacher_name, teacher_email, teacher_phone_number, teacher_specialization, teacher_notes, teacher_status, teacher_created_at, teacher_updated_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->execute([$teacher_name, $teacher_email, $teacher_phone_number, $teacher_specialization, $teacher_notes]);
            $message = 'Teacher added successfully!';
        }
    }
    
    if (isset($_POST['delete_teacher'])) {
        $teacher_id = $_POST['teacher_id'] ?? 0;
        if ($teacher_id) {
            $stmt = $pdo->prepare("UPDATE teachers SET teacher_status = 0 WHERE teacher_id = ?");
            $stmt->execute([$teacher_id]);
            $message = 'Teacher removed successfully!';
        }
    }
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->query("SELECT COUNT(*) FROM teachers WHERE teacher_status = 1");
$totalTeachers = $stmt->fetchColumn();
$totalPages = ceil($totalTeachers / $perPage);

$stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_status = 1 ORDER BY teacher_name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE student_status = 1");
$totalStudents = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE student_status = 1");
$stmt->execute();
$activeStudents = $stmt->fetchColumn();
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
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Teachers</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
        <button onclick="openAddModal()" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-inter text-sm font-medium px-4 py-2 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Teacher
        </button>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        <?php if ($message): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-2xl">👩‍🏫</div>
                    <div>
                        <p class="text-sm text-slate-500">Total Teachers</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800"><?= $totalTeachers ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-2xl">👶</div>
                    <div>
                        <p class="text-sm text-slate-500">Active Students</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800"><?= $totalStudents ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-2xl">📊</div>
                    <div>
                        <p class="text-sm text-slate-500">Avg Students/Teacher</p>
                        <p class="font-poppins text-2xl font-bold text-slate-800"><?= $totalTeachers > 0 ? round($totalStudents / $totalTeachers, 1) : 0 ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teachers Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-poppins text-lg font-semibold text-slate-800">All Teachers</h2>
                <p class="text-xs text-slate-400 mt-0.5">Manage your teaching staff</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Teacher</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Email</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Phone</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Specialization</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($teachers)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">No teachers found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                                        <?= strtoupper(substr($teacher['teacher_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($teacher['teacher_name']) ?></p>
                                        <p class="text-xs text-slate-400">ID: <?= $teacher['teacher_id'] ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600"><?= htmlspecialchars($teacher['teacher_email'] ?? '—') ?></td>
                            <td class="px-4 py-4 text-sm text-slate-600"><?= htmlspecialchars($teacher['teacher_phone_number'] ?? '—') ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                    <?= htmlspecialchars($teacher['teacher_specialization'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-1">
                                    <button onclick="openEditModal(<?= $teacher['teacher_id'] ?>, '<?= htmlspecialchars($teacher['teacher_name']) ?>', '<?= htmlspecialchars($teacher['teacher_email'] ?? '') ?>', '<?= htmlspecialchars($teacher['teacher_phone_number'] ?? '') ?>', '<?= htmlspecialchars($teacher['teacher_specialization'] ?? '') ?>', '<?= htmlspecialchars($teacher['teacher_notes'] ?? '') ?>')" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this teacher?');">
                                        <input type="hidden" name="teacher_id" value="<?= $teacher['teacher_id'] ?>">
                                        <button type="submit" name="delete_teacher" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
<div id="teacherModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6">
        <h3 id="modalTitle" class="font-poppins text-lg font-semibold text-slate-800 mb-4">Add Teacher</h3>
        <form method="POST" id="teacherForm">
            <input type="hidden" name="teacher_id" id="teacherId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Name *</label>
                    <input type="text" name="teacher_name" id="teacherName" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter teacher name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                    <input type="email" name="teacher_email" id="teacherEmail" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="teacher@example.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Phone</label>
                    <input type="text" name="teacher_phone_number" id="teacherPhone" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="0123456789">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Specialization</label>
                    <input type="text" name="teacher_specialization" id="teacherSpecialization" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="e.g. Math, Science">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
                    <textarea name="teacher_notes" id="teacherNotes" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Additional notes..."></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" name="save_teacher" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-4 py-2.5 rounded-xl transition-colors">Save</button>
                <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Teacher';
    document.getElementById('teacherForm').reset();
    document.getElementById('teacherId').value = '';
    document.getElementById('teacherModal').classList.remove('hidden');
    document.getElementById('teacherModal').classList.add('flex');
}

function openEditModal(id, name, email, phone, specialization, notes) {
    document.getElementById('modalTitle').textContent = 'Edit Teacher';
    document.getElementById('teacherId').value = id;
    document.getElementById('teacherName').value = name;
    document.getElementById('teacherEmail').value = email;
    document.getElementById('teacherPhone').value = phone;
    document.getElementById('teacherSpecialization').value = specialization;
    document.getElementById('teacherNotes').value = notes;
    
    // Add hidden input for update
    const form = document.getElementById('teacherForm');
    let updateInput = form.querySelector('input[name="update_teacher"]');
    if (!updateInput) {
        updateInput = document.createElement('input');
        updateInput.type = 'hidden';
        updateInput.name = 'update_teacher';
        form.appendChild(updateInput);
    }
    updateInput.value = '1';
    
    document.getElementById('teacherModal').classList.remove('hidden');
    document.getElementById('teacherModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('teacherModal').classList.add('hidden');
    document.getElementById('teacherModal').classList.remove('flex');
}

document.getElementById('teacherModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher'])) {
    $teacher_id = $_POST['teacher_id'] ?? null;
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $teacher_email = trim($_POST['teacher_email'] ?? '');
    $teacher_phone_number = trim($_POST['teacher_phone_number'] ?? '');
    $teacher_specialization = trim($_POST['teacher_specialization'] ?? '');
    $teacher_notes = trim($_POST['teacher_notes'] ?? '');
    
    if ($teacher_id && !empty($teacher_name)) {
        $stmt = $pdo->prepare("UPDATE teachers SET teacher_name = ?, teacher_email = ?, teacher_phone_number = ?, teacher_specialization = ?, teacher_notes = ?, teacher_updated_at = NOW() WHERE teacher_id = ?");
        $stmt->execute([$teacher_name, $teacher_email, $teacher_phone_number, $teacher_specialization, $teacher_notes, $teacher_id]);
        echo "<script>window.location.href = '?updated=1';</script>";
    }
}
?>
</body>
</html>