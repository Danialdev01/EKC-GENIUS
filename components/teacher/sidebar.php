<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = dirname($_SERVER['PHP_SELF']);
$currentDir = basename($currentPath);
$teacherName  = $_SESSION['user_name'] ?? 'Teacher';
?>

<!-- Sidebar Overlay (Mobile) -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" onclick="toggleTeacherSidebar()"></div>

<!-- Sidebar -->
<aside id="teacherSidebar" class="fixed top-0 left-0 h-full w-64 bg-slate-900 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 flex flex-col">

    <!-- Logo -->
    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-700/60">
        <a href="./" class="flex items-center gap-2 font-poppins text-lg font-bold select-none">
            <span class="text-2xl">🧠</span>
            <span class="text-slate-100">EKC</span><span class="text-indigo-400">Genius</span>
        </a>
        <button onclick="toggleTeacherSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Teacher Info -->
    <div class="px-5 py-4 border-b border-slate-700/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-base font-semibold text-white shrink-0">
                <?= strtoupper(substr($teacherName, 0, 1)); ?>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-100 truncate"><?= htmlspecialchars($teacherName); ?></p>
                <p class="text-xs text-indigo-400 font-medium">Teacher</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">

        <!-- Dashboard -->
        <?php $dashboardActive = in_array($currentPage, ['index', 'add', 'edit']) && $currentDir === 'teachers'; ?>
        <a href="<?php echo $location_index; ?>/teachers/" class="teacher-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= $dashboardActive ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <!-- Students -->
        <?php $studentActive = in_array($currentPage, ['index', 'add', 'edit']) && $currentDir === 'students'; ?>
        <a href="<?php echo $location_index; ?>/teachers/students/" class="teacher-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= $studentActive ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Students
        </a>

        <!-- Activities -->
        <?php $activityActive = in_array($currentPage, ['index', 'add', 'edit']) && $currentDir === 'activities'; ?>
        <a href="<?php echo $location_index; ?>/teachers/activities/" class="teacher-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= $activityActive ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Activities
        </a>

        <!-- Assessments -->
        <?php $assessmentActive = in_array($currentPage, ['index', 'add', 'edit']) && $currentDir === 'assessments'; ?>
        <a href="<?php echo $location_index; ?>/teachers/assessments/" class="teacher-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= $assessmentActive ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Assessments
        </a>

        <!-- Attendance -->
        <?php $attendanceActive = in_array($currentPage, ['index', 'add', 'edit']) && $currentDir === 'attendance'; ?>
        <a href="<?php echo $location_index; ?>/teachers/attendance/" class="teacher-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= $attendanceActive ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Attendance
        </a>

        <!-- Section divider -->
        <div class="pt-3 pb-1 px-3">
            <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Analytics</p>
        </div>

        <!-- Development Analysis -->
        <?php $developmentActive = in_array($currentPage, ['index', 'add', 'edit']) && $currentDir === 'analysis'; ?>
        <a href="<?php echo $location_index; ?>/teachers/analysis/" class="teacher-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= $developmentActive ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Development Analysis
        </a>

        <!-- Alert & Warnings -->
        <?php $alertsActive = $currentDir === 'alerts'; ?>
        <a href="<?php echo $location_index; ?>/teachers/alerts/" class="teacher-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= $alertsActive ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <div class="relative w-5 h-5 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            Alert &amp; Warnings
        </a>

        <!-- AI Recommendations -->
        <a href="<?php echo $location_index; ?>/teachers/recommendations/" class="teacher-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= $currentPage === 'ai-recommendations' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            AI Recommendations
        </a>

        <!-- Section divider -->
        <div class="pt-3 pb-1 px-3">
            <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Account</p>
        </div>

        <!-- Profile -->
        <a href="<?php echo $location_index; ?>/teachers/profile.php" class="teacher-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
            <?= $currentPage === 'profile' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Profile
        </a>

    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-slate-700/60">
        <a href="<?php echo $location_index; ?>/backend/logout.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:bg-red-500/15 hover:text-red-400 transition-all duration-150">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Logout
        </a>
    </div>
</aside>

<script>
function toggleTeacherSidebar() {
    const sidebar = document.getElementById('teacherSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

// Close sidebar when clicking a nav link on mobile
document.querySelectorAll('.teacher-nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 1024) {
            toggleTeacherSidebar();
        }
    });
});
</script>
