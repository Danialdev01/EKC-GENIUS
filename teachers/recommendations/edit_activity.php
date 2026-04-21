<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';

$authUser = requireAuth('teacher');
$pageTitle = 'Convert to Activity';
$cssDepth = '../../public/css';
$location_index = '../..';

$activity_id = $_GET['activity_id'] ?? null;
$assessment_id = $_GET['assessment_id'] ?? null;
$message = '';

if (!$activity_id || !$assessment_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM activites WHERE activity_id = ? AND activity_status = 4");
$stmt->execute([$activity_id]);
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM assessments WHERE assessment_id = ? AND assessment_status = 1");
$stmt->execute([$assessment_id]);
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    header('Location: index.php');
    exit;
}

$currentMonth = date('n');
$currentYear = date('Y');

$stmt = $pdo->prepare("
    SELECT student_id FROM student_assessments 
    WHERE assessment_id = ? AND student_assessment_month = ? AND student_assessment_year = ? 
    AND student_assessment_status = 1 AND student_assessment_value <= 2.5
");
$stmt->execute([$assessment_id, $currentMonth, $currentYear]);
$defaultStudentIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'student_id');

$stmt = $pdo->prepare("SELECT student_id FROM assignments WHERE activity_id = ? AND assignment_status = 1");
$stmt->execute([$activity_id]);
$existingAssignedIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'student_id');

$preSelectedStudents = array_unique(array_merge($defaultStudentIds, $existingAssignedIds));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert_activity'])) {
    $activity_name = trim($_POST['activity_name'] ?? '');
    $activity_description = trim($_POST['activity_description'] ?? '');
    $activity_type = trim($_POST['activity_type'] ?? '');
    $activity_active_at = $_POST['activity_active_at'] ?? null;
    $selected_students = $_POST['students'] ?? [];
    
    if (!empty($activity_name) && !empty($activity_description)) {
        $stmt = $pdo->prepare("UPDATE activites SET activity_name = ?, activity_description = ?, activity_type = ?, activity_active_at = ?, activity_status = 1, activity_updated_at = NOW() WHERE activity_id = ?");
        $stmt->execute([$activity_name, $activity_description, $activity_type, $activity_active_at, $activity_id]);
        
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        
        if (!empty($selected_students)) {
            $stmt = $pdo->prepare("INSERT INTO assignments (activity_id, student_id, assignment_status, assignment_created_at, assignment_updated_at) VALUES (?, ?, 1, NOW(), NOW())");
            foreach ($selected_students as $student_id) {
                $stmt->execute([$activity_id, $student_id]);
            }
        }
        
        header('Location: index.php?success=1');
        exit;
    }
}

$search = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

$params = [];
$where = "s.student_status = 1";

if ($search) {
    $where .= " AND s.student_name LIKE ?";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where .= " AND s.category_id = ?";
    $params[] = $category_filter;
}

$count_sql = "SELECT COUNT(*) FROM students s LEFT JOIN categories c ON c.category_id = s.category_id AND c.category_status = 1 WHERE $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

$sql = "SELECT s.student_id, s.student_name, s.student_year_of_birth, c.category_name, c.category_id FROM students s LEFT JOIN categories c ON c.category_id = s.category_id AND c.category_status = 1 WHERE $where ORDER BY s.student_name LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT category_id, category_name FROM categories WHERE category_status = 1 ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activity_date = $_GET['activity_date'] ?? $activity['activity_active_at'] ?? null;
$conflicting_students = [];

if ($activity_date) {
    $activity_date_only = date('Y-m-d', strtotime($activity_date));
    $stmt = $pdo->prepare("
        SELECT DISTINCT a.student_id 
        FROM assignments a
        INNER JOIN activites act ON act.activity_id = a.activity_id AND act.activity_status = 1
        WHERE a.assignment_status = 1 
        AND a.activity_id != ?
        AND DATE(act.activity_active_at) = ?
    ");
    $stmt->execute([$activity_id, $activity_date_only]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $conflicting_students[] = $row['student_id'];
    }
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    echo json_encode([
        'students' => $students,
        'conflicting_students' => $conflicting_students,
        'assigned_students' => $preSelectedStudents,
        'total' => $total,
        'total_pages' => $total_pages,
        'page' => $page,
        'per_page' => $per_page,
        'offset' => $offset
    ]);
    exit;
}
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
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Convert to Activity</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
        <a href="view.php?assessment_id=<?= (int)$assessment_id ?>" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back
        </a>
    </header>

    <div class="flex-1 p-4 lg:p-8">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-100 bg-amber-50">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-amber-800">Converting recommendation for: <?= htmlspecialchars($assessment['assessment_title']) ?></span>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-6">
                <input type="hidden" name="convert_activity" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Activity Name *</label>
                        <input type="text" name="activity_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter activity name" value="<?= htmlspecialchars($activity['activity_name'] ?? '') ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Type</label>
                        <input type="text" name="activity_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="e.g., Sports, Arts, Academic" value="<?= htmlspecialchars($activity['activity_type'] ?? '') ?>">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Description *</label>
                    <textarea name="activity_description" required rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter activity description"><?= htmlspecialchars($activity['activity_description'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Activity Date & Time</label>
                    <input type="datetime-local" name="activity_active_at" id="activityDate" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" value="<?= htmlspecialchars($activity['activity_active_at'] ?? '') ?>">
                </div>
                
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-emerald-800">Auto-assigned Students</span>
                    </div>
                    <p class="text-xs text-emerald-600">Students who scored below 2.5 for this assessment are automatically selected. You can add more students below.</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Assign Additional Students</label>
                    
                    <div class="flex flex-col sm:flex-row gap-3 mb-3">
                        <div class="flex-1">
                            <input type="text" id="studentSearch" value="" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Search student name...">
                        </div>
                        <div class="sm:w-48">
                            <select id="categoryFilter" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[600px]">
                                <thead>
                                    <tr class="bg-slate-50 text-left">
                                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase w-10">
                                            <input type="checkbox" id="selectAllStudents" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Student Name</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Category</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Auto-assigned</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Conflict</th>
                                    </tr>
                                </thead>
                                <tbody id="studentsTableBody" class="divide-y divide-slate-100">
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-slate-400">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-3 px-2">
                        <span id="paginationInfo" class="text-sm text-slate-500"></span>
                        <div id="paginationControls" class="flex items-center gap-1"></div>
                    </div>
                    
                    <p class="text-xs text-slate-400 mt-2">Select students to assign to this activity. Students who scored below 2.5 are auto-selected.</p>
                </div>
                
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-inter font-medium px-6 py-3 rounded-xl transition-colors">
                        Convert to Activity
                    </button>
                    <a href="view.php?assessment_id=<?= (int)$assessment_id ?>" class="px-6 py-3 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
let currentPage = 1;
let selectedStudents = new Set(<?= json_encode(array_map('strval', $preSelectedStudents)) ?>);
let searchTimeout;
let currentSearch = '';
let currentCategory = '';
let activityId = <?= (int)$activity_id ?>;
let defaultStudents = <?= json_encode(array_map('strval', $defaultStudentIds)) ?>;

function loadStudents(page = 1) {
    currentPage = page;
    const url = new URL(window.location.href);
    url.searchParams.set('ajax', '1');
    url.searchParams.set('page', page);
    url.searchParams.set('search', currentSearch);
    url.searchParams.set('category', currentCategory);
    
    const activityDate = document.getElementById('activityDate').value;
    if (activityDate) {
        url.searchParams.set('activity_date', activityDate);
    }
    
    fetch(url.toString())
        .then(res => res.json())
        .then(data => {
            renderTable(data.students, data.conflicting_students);
            renderPagination(data);
        });
}

function renderTable(students, conflictingStudents) {
    const tbody = document.getElementById('studentsTableBody');
    if (!students.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">No students found</td></tr>';
        return;
    }
    
    let html = '';
    students.forEach(student => {
        const hasConflict = conflictingStudents.includes(student.student_id);
        const isChecked = selectedStudents.has(String(student.student_id));
        const isAutoAssigned = defaultStudents.includes(String(student.student_id));
        html += `
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <input type="checkbox" name="students[]" value="${student.student_id}" class="student-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" ${isChecked ? 'checked' : ''} onchange="toggleStudent(${student.student_id}, this.checked)">
                </td>
                <td class="px-4 py-3 text-slate-700">${student.student_name}</td>
                <td class="px-4 py-3 text-slate-500">${student.category_name || '—'}</td>
                <td class="px-4 py-3">
                    ${isAutoAssigned ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Auto</span>' : '<span class="text-slate-400">—</span>'}
                </td>
                <td class="px-4 py-3">
                    ${hasConflict ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Same Date</span>' : '<span class="text-slate-400">—</span>'}
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function renderPagination(data) {
    const info = document.getElementById('paginationInfo');
    const controls = document.getElementById('paginationControls');
    
    if (data.total_pages <= 1) {
        info.textContent = '';
        controls.innerHTML = '';
        return;
    }
    
    const start = data.offset + 1;
    const end = Math.min(data.offset + data.per_page, data.total);
    info.textContent = `Showing ${start}-${end} of ${data.total}`;
    
    let html = '';
    const totalPages = data.total_pages;
    const currentPage = data.page;
    
    if (currentPage > 1) {
        html += `<button onclick="loadStudents(${currentPage - 1})" class="px-3 py-1.5 text-sm rounded-lg text-slate-600 hover:bg-slate-100">Prev</button>`;
    }
    
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPage ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100';
        html += `<button onclick="loadStudents(${i})" class="px-3 py-1.5 text-sm rounded-lg ${active}">${i}</button>`;
    }
    
    if (currentPage < totalPages) {
        html += `<button onclick="loadStudents(${currentPage + 1})" class="px-3 py-1.5 text-sm rounded-lg text-slate-600 hover:bg-slate-100">Next</button>`;
    }
    
    controls.innerHTML = html;
}

function toggleStudent(studentId, checked) {
    if (checked) {
        selectedStudents.add(String(studentId));
    } else {
        selectedStudents.delete(String(studentId));
    }
    updateSelectAllCheckbox();
}

function updateSelectAllCheckbox() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
    document.getElementById('selectAllStudents').checked = allChecked;
}

document.getElementById('selectAllStudents').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = this.checked;
        toggleStudent(cb.value, this.checked);
    });
});

document.getElementById('studentSearch').addEventListener('input', function(e) {
    currentSearch = e.target.value;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadStudents(1), 300);
});

document.getElementById('categoryFilter').addEventListener('change', function(e) {
    currentCategory = e.target.value;
    loadStudents(1);
});

document.getElementById('activityDate').addEventListener('change', function() {
    loadStudents(1);
});

loadStudents(1);
</script>

</body>
</html>