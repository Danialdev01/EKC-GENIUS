<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
$authUser = requireAuth('teacher');
$pageTitle = 'Students';
$cssDepth = '../../public/css';
$location_index = '../..';

$studentId = $_GET['id'] ?? null;

// Store globally for included components
$GLOBALS['studentId'] = $studentId;
?>
<?php include '../../components/teacher/header.php'; ?>

<?php include '../../components/teacher/sidebar.php'; ?>

<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleTeacherSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800"><?= $studentId ? 'Student Details' : 'Students' ?></h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8">
        <?php if ($studentId): ?>
            <?php include '../components/info_student.php'; ?>
        <?php else: ?>
            <?php include '../components/all_students.php'; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>