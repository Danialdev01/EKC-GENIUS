<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
$authUser = requireAuth('admin');
$pageTitle = 'Add Student';
$cssDepth = '../../public/css';
$location_index = '../..';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_student'])) {
        $student_name = trim($_POST['student_name'] ?? '');
        $student_ic = trim($_POST['student_ic'] ?? '');
        $student_year_of_birth = $_POST['student_year_of_birth'] ?? null;
        $category_id = $_POST['category_id'] ?? null;
        $student_parent_name = trim($_POST['student_parent_name'] ?? '');
        $student_parent_email = trim($_POST['student_parent_email'] ?? '');
        $student_parent_number = trim($_POST['student_parent_number'] ?? '');
        $student_notes = trim($_POST['student_notes'] ?? '');
        
        $student_ic = str_replace('-', '', $student_ic);
        
        if (!empty($student_name)) {
            $stmt = $pdo->prepare("INSERT INTO students (student_name, student_ic, student_year_of_birth, category_id, student_parent_name, student_parent_email, student_parent_number, student_notes, student_status, student_created_at, student_updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->execute([$student_name, $student_ic, $student_year_of_birth, $category_id, $student_parent_name, $student_parent_email, $student_parent_number, $student_notes]);
            $message = 'Student added successfully!';
            header('Location: index.php?success=1');
            exit;
        }
    }
}

$stmt = $pdo->query("SELECT category_id, category_name FROM categories WHERE category_status = 1 ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Add Student</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
        <a href="index.php" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back
        </a>
    </header>

    <div class="flex-1 p-4 lg:p-8">
        <?php if ($message): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="save_student" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Student Name *</label>
                        <input type="text" name="student_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter student name">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">IC Number</label>
                        <input type="text" name="student_ic" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter IC (e.g. 060802030010)" maxlength="12">
                        <p class="text-xs text-slate-400 mt-1">Without dashes (-)</p>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Year of Birth</label>
                            <input type="number" name="student_year_of_birth" min="2010" max="<?= date('Y') ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="e.g. 2018">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                            <select name="category_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-slate-200">
                        <p class="text-sm font-medium text-slate-700 mb-3">Parent / Guardian Details</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Parent Name</label>
                        <input type="text" name="student_parent_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter parent name">
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Parent Email</label>
                            <input type="email" name="student_parent_email" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter email">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Parent Phone</label>
                            <input type="text" name="student_parent_number" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter phone number">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
                        <textarea name="student_notes" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Additional notes..."></textarea>
                    </div>
                    
                    <div class="flex items-center gap-3 pt-4">
                        <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium py-3 rounded-xl transition-colors">
                            Save Student
                        </button>
                        <a href="index.php" class="px-6 py-3 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.querySelector('input[name="student_ic"]')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/-/g, '');
    if (e.target.value.length > 12) e.target.value = e.target.value.slice(0, 12);
});
</script>
</body>
</html>