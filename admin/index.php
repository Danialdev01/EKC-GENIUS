<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../backend/auth.php';
$location_index = "..";
$authUser  = requireAuth('admin');
$pageTitle = 'Dashboard';
$cssDepth  = '../public/css';

$stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE student_status = 1");
$totalStudents = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM teachers WHERE teacher_status = 1");
$totalTeachers = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE category_status = 1");
$totalCategories = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM attendances WHERE DATE(attendance_datetime) = CURDATE() AND attendance_status = 1");
$todayAttendance = $stmt->fetchColumn() ?: 0;

$attendanceRate = $totalStudents > 0 ? round(($todayAttendance / $totalStudents) * 100) : 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM invoices WHERE MONTH(CURDATE()) = MONTH(invoice_due_month) AND YEAR(CURDATE()) = YEAR(invoice_due_year)");
$totalInvoicesThisMonth = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM invoices WHERE MONTH(CURDATE()) = MONTH(invoice_due_month) AND YEAR(CURDATE()) = YEAR(invoice_due_year) AND invoice_status = 1");
$paidInvoices = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM invoices WHERE MONTH(CURDATE()) = MONTH(invoice_due_month) AND YEAR(CURDATE()) = YEAR(invoice_due_year) AND invoice_status = 0");
$pendingInvoices = $stmt->fetchColumn() ?: 0;

$categoryData = [];
$stmt = $pdo->query("SELECT c.category_name, COUNT(s.student_id) as student_count 
    FROM categories c 
    LEFT JOIN students s ON c.category_id = s.category_id AND s.student_status = 1 
    WHERE c.category_status = 1 
    GROUP BY c.category_id");
$categoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$monthlyTrends = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('n', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_status = 1 AND MONTH(student_created_at) = ? AND YEAR(student_created_at) = ?");
    $stmt->execute([$month, $year]);
    $monthlyTrends[] = [
        'month' => date('M', strtotime("-$i months")),
        'count' => $stmt->fetchColumn() ?: 0
    ];
}

$recentStudents = [];
$stmt = $pdo->query("SELECT student_id, student_name, student_created_at FROM students WHERE student_status = 1 ORDER BY student_created_at DESC LIMIT 5");
$recentStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avgScores = [];
$stmt = $pdo->query("SELECT ROUND(AVG(student_assessment_value), 1) as avg_score, COUNT(DISTINCT student_id) as student_count 
    FROM student_assessments 
    WHERE student_assessment_status = 1 
    AND student_assessment_month = MONTH(CURDATE()) 
    AND student_assessment_year = YEAR(CURDATE())");
$assessmentStats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<?php include '../components/teacher/header.php'; ?>
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen">
        <!-- Top Header -->
        <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-500 hover:text-slate-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h1 class="font-poppins text-xl font-semibold">Dashboard</h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-500"><?= date('F d, Y'); ?></span>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="p-4 lg:p-8 space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Total Students</p>
                            <p class="font-poppins text-2xl font-bold text-slate-800"><?= $totalStudents ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-2xl">👶</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Active Teachers</p>
                            <p class="font-poppins text-2xl font-bold text-slate-800"><?= $totalTeachers ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-2xl">👩‍🏫</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Today's Attendance</p>
                            <p class="font-poppins text-2xl font-bold text-slate-800"><?= $attendanceRate ?>%</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-2xl">✅</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Pending Payments</p>
                            <p class="font-poppins text-2xl font-bold text-slate-800"><?= $pendingInvoices ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-2xl">💰</div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Student Trends Chart -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="font-poppins text-lg font-semibold mb-4">Student Enrollment Trends</h2>
                    <div class="h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Category Distribution -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="font-poppins text-lg font-semibold mb-4">Students by Category</h2>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Second Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Payment Overview -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="font-poppins text-lg font-semibold mb-4">Payment Overview</h2>
                    <div class="h-48">
                        <canvas id="paymentChart"></canvas>
                    </div>
                    <div class="mt-4 flex justify-between text-sm">
                        <span class="text-slate-500"><?= $paidInvoices ?> Paid</span>
                        <span class="text-slate-500"><?= $pendingInvoices ?> Pending</span>
                    </div>
                </div>

                <!-- Recent Students -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="font-poppins text-lg font-semibold mb-4">Recent Students</h2>
                    <div class="space-y-3">
                        <?php if (empty($recentStudents)): ?>
                        <p class="text-sm text-slate-400 text-center py-4">No recent students</p>
                        <?php else: ?>
                        <?php foreach ($recentStudents as $student): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                <?= strtoupper(substr($student['student_name'], 0, 1)) ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate"><?= htmlspecialchars($student['student_name']) ?></p>
                                <p class="text-xs text-slate-400"><?= date('M d, Y', strtotime($student['student_created_at'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="font-poppins text-lg font-semibold mb-4">Quick Stats</h2>
                    <div class="space-y-4">
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <p class="text-xs text-slate-500">Categories</p>
                            <p class="text-lg font-bold text-slate-800"><?= $totalCategories ?></p>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <p class="text-xs text-slate-500">This Month's Invoices</p>
                            <p class="text-lg font-bold text-slate-800"><?= $totalInvoicesThisMonth ?></p>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <p class="text-xs text-slate-500">Avg Assessment Score</p>
                            <p class="text-lg font-bold text-slate-800"><?= $assessmentStats['avg_score'] ?? '-' ?>/5</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h2 class="font-poppins text-lg font-semibold mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="students/" class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all">
                        <span class="text-2xl">👶</span>
                        <span class="text-sm font-medium">Add Student</span>
                    </a>
                    <a href="teachers/" class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all">
                        <span class="text-2xl">👩‍🏫</span>
                        <span class="text-sm font-medium">Add Teacher</span>
                    </a>
                    <a href="attendance/" class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all">
                        <span class="text-2xl">📋</span>
                        <span class="text-sm font-medium">Take Attendance</span>
                    </a>
                    <a href="assessments/" class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all">
                        <span class="text-2xl">📊</span>
                        <span class="text-sm font-medium">View Assessments</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Trend Chart - Monthly student enrollment
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthlyTrends, 'month')) ?>,
                datasets: [{
                    label: 'New Students',
                    data: <?= json_encode(array_column($monthlyTrends, 'count')) ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // Category Chart - Doughnut
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($categoryData, 'category_name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($categoryData, 'student_count')) ?>,
                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });

        // Payment Chart - Pie
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'pie',
            data: {
                labels: ['Paid', 'Pending'],
                datasets: [{
                    data: [<?= $paidInvoices ?>, <?= $pendingInvoices ?>],
                    backgroundColor: ['#10b981', '#f59e0b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>