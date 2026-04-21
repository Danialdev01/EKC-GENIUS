<?php
session_start();
require_once '../config/connect.php';
require_once '../backend/auth.php';
$authUser = requireAuth('teacher');
$pageTitle = 'Profile';
$cssDepth = '../public/css';
$location_index = '..';

$teacherId = $authUser['id'];
$message = '';

$stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ? AND teacher_status = 1");
$stmt->execute([$teacherId]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $teacher_notes = trim($_POST['teacher_notes'] ?? '');
    
    if (!empty($teacher_name)) {
        $stmt = $pdo->prepare("UPDATE teachers SET teacher_name = ?, teacher_notes = ?, teacher_updated_at = NOW() WHERE teacher_id = ?");
        $stmt->execute([$teacher_name, $teacher_notes, $teacherId]);
        $message = 'Profile updated successfully!';
        
        $_SESSION['user_name'] = $teacher_name;
        
        $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ? AND teacher_status = 1");
        $stmt->execute([$teacherId]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<?php include '../components/teacher/header.php'; ?>

<?php include '../components/teacher/sidebar.php'; ?>

<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleTeacherSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Profile</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
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
                    <h2 class="font-poppins text-lg font-semibold text-slate-800">Teacher Profile</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Update your personal information</p>
                </div>
                
                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold">
                            <?= strtoupper(substr($teacher['teacher_name'] ?? '', 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Profile Photo</p>
                            <p class="text-xs text-slate-400">Initials will be displayed</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Full Name</label>
                        <input type="text" name="teacher_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter your name" value="<?= htmlspecialchars($teacher['teacher_name'] ?? '') ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                        <input type="email" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500" value="<?= htmlspecialchars($teacher['teacher_email'] ?? 'Not set') ?>" disabled>
                        <p class="text-xs text-slate-400 mt-1">Contact admin to change email</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Notes / Bio</label>
                        <textarea name="teacher_notes" rows="4" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Tell us about yourself..."><?= htmlspecialchars($teacher['teacher_notes'] ?? '') ?></textarea>
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