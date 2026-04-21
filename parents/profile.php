<?php
session_start();
require_once '../config/connect.php';
require_once '../backend/auth.php';
$authUser = requireAuth('parent');
$pageTitle = 'Profile';
$cssDepth = '../public/css';

$studentId = $authUser['id'];
$message = '';

$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND student_status = 1");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    session_unset(); session_destroy();
    header('Location: ../index.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $student_parent_name = trim($_POST['student_parent_name'] ?? '');
    $student_parent_email = trim($_POST['student_parent_email'] ?? '');
    $student_parent_number = trim($_POST['student_parent_number'] ?? '');
    $student_notes = trim($_POST['student_notes'] ?? '');
    
    if (!empty($student_parent_name)) {
        $stmt = $pdo->prepare("UPDATE students SET student_parent_name = ?, student_parent_email = ?, student_parent_number = ?, student_notes = ?, student_updated_at = NOW() WHERE student_id = ?");
        $stmt->execute([$student_parent_name, $student_parent_email, $student_parent_number, $student_notes, $studentId]);
        $message = 'Profile updated successfully!';
        
        $_SESSION['user_name'] = $student_parent_name;
        
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND student_status = 1");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<?php include '../components/teacher/header.php'; ?>

<!-- Parent Sidebar -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" onclick="toggleParentSidebar()"></div>
<aside id="parentSidebar" class="fixed top-0 left-0 h-full w-64 bg-slate-900 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 flex flex-col">
    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-700/60">
        <a href="../index.php" class="flex items-center gap-2 font-poppins text-lg font-bold">
            <span class="text-2xl">🧠</span>
            <span class="text-slate-100">EKC</span><span class="text-indigo-400">Genius</span>
        </a>
        <button onclick="toggleParentSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="px-5 py-4 border-b border-slate-700/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-base font-semibold text-white shrink-0">
                <?= strtoupper(substr($authUser['name'], 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-100 truncate"><?= htmlspecialchars($authUser['name']) ?></p>
                <p class="text-xs text-indigo-400 font-medium">Parent / Guardian</p>
            </div>
        </div>
    </div>
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        <?php
        $navItems = [
            ['href' => 'index.php',    'label' => 'Overview',     'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['href' => 'progress.php', 'label' => 'Progress',     'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['href' => 'reports.php',  'label' => 'Reports',      'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['href' => 'invoices.php', 'label' => 'Invoices',     'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['href' => 'profile.php',  'label' => 'Profile',      'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ];
        $cur = basename($_SERVER['PHP_SELF'], '.php');
        foreach ($navItems as $n): ?>
        <a href="<?= $n['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= ($cur === basename($n['href'], '.php')) ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $n['icon'] ?>"/>
            </svg>
            <?= $n['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="p-4 border-t border-slate-700/60">
        <a href="../backend/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:bg-red-500/15 hover:text-red-400 transition-all">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleParentSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Profile</h1>
                <p class="text-xs text-slate-400"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8">
        <?php if ($message): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="font-poppins text-lg font-semibold text-slate-800">Parent / Guardian Profile</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Update your contact information</p>
                </div>
                
                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold">
                            <?= strtoupper(substr($student['student_parent_name'] ?? '', 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Profile Photo</p>
                            <p class="text-xs text-slate-400">Initials will be displayed</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Child's Name</label>
                        <input type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500" value="<?= htmlspecialchars($student['student_name'] ?? '') ?>" disabled>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Parent / Guardian Name *</label>
                        <input type="text" name="student_parent_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter your name" value="<?= htmlspecialchars($student['student_parent_name'] ?? '') ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                        <input type="email" name="student_parent_email" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter email" value="<?= htmlspecialchars($student['student_parent_email'] ?? '') ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Phone Number</label>
                        <input type="text" name="student_parent_number" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter phone number" value="<?= htmlspecialchars($student['student_parent_number'] ?? '') ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Additional Notes</label>
                        <textarea name="student_notes" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Any additional information..."><?= htmlspecialchars($student['student_notes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium py-3 rounded-xl transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function toggleParentSidebar() {
    const sidebar = document.getElementById('parentSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>
</body>
</html>