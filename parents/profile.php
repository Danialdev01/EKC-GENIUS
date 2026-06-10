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

<?php include __DIR__ . '/../components/parent/sidebar.php'; ?>

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

</body>
</html>