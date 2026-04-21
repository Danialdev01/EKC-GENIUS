<?php
session_start();
require_once '../../config/connect.php';
require_once '../../backend/auth.php';
requireAuth('admin');
$pageTitle = 'Attendance';
$cssDepth = '../../public/css';
$location_index = '../..';

$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selectedDateObj = new DateTime($selectedDate);

$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : '';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$searchQuery = trim($_GET['search'] ?? '');

$whereClause = "WHERE s.student_status = 1";
$params = [];

if (!empty($searchQuery)) {
    $whereClause .= " AND s.student_name LIKE ?";
    $params[] = '%' . $searchQuery . '%';
}

if (!empty($selectedCategory)) {
    $whereClause .= " AND s.category_id = ?";
    $params[] = $selectedCategory;
}

$countSql = "SELECT COUNT(*) FROM students s $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalStudents = $stmt->fetchColumn();
$totalPages = ceil($totalStudents / $perPage);

$sql = "
    SELECT s.student_id, s.student_name, s.category_id
    FROM students s
    $whereClause
    ORDER BY s.student_name ASC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT category_id, category_name FROM categories WHERE category_status = 1 ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getCategoryName($pdo, $categoryId) {
    if (!$categoryId) return '—';
    $stmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ? AND category_status = 1");
    $stmt->execute([$categoryId]);
    return $stmt->fetchColumn() ?: '—';
}

function getAttendance($pdo, $studentId, $date) {
    $stmt = $pdo->prepare("
        SELECT attendance_id, attendance_type, attendance_notes, attendance_datetime
        FROM attendances 
        WHERE student_id = ? AND attendance_status = 1
    ");
    $stmt->execute([$studentId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        if (!empty($row['attendance_datetime'])) {
            $recordDate = date('Y-m-d', strtotime($row['attendance_datetime']));
            if ($recordDate === $date) {
                return $row;
            }
        }
    }
    return null;
}

$studentsWithAttendance = [];
foreach ($students as $student) {
    $attendance = getAttendance($pdo, $student['student_id'], $selectedDate);
    $student['attendance'] = $attendance;
    $student['category_name'] = getCategoryName($pdo, $student['category_id'] ?? null);
    
    if ($attendance) {
        $student['attendance_type'] = (int)$attendance['attendance_type'];
    } else {
        $student['attendance_type'] = 0;
    }
    
    $studentsWithAttendance[] = $student;
}

if ($selectedStatus !== 'all') {
    if ($selectedStatus === 'not_set') {
        usort($studentsWithAttendance, function($a, $b) {
            if ($a['attendance_type'] === 0 && $b['attendance_type'] !== 0) return -1;
            if ($a['attendance_type'] !== 0 && $b['attendance_type'] === 0) return 1;
            return strcmp($a['student_name'], $b['student_name']);
        });
    } else {
        $statusMap = ['present' => 1, 'late' => 2, 'absent' => 3];
        $filterType = $statusMap[$selectedStatus] ?? 0;
        
        usort($studentsWithAttendance, function($a, $b) use ($filterType) {
            $aHasType = $a['attendance_type'] === $filterType;
            $bHasType = $b['attendance_type'] === $filterType;
            
            if ($aHasType && !$bHasType) return -1;
            if (!$aHasType && $bHasType) return 1;
            if ($a['attendance_type'] === 0 && $b['attendance_type'] !== 0) return -1;
            if ($a['attendance_type'] !== 0 && $b['attendance_type'] === 0) return 1;
            return strcmp($a['student_name'], $b['student_name']);
        });
    }
} else {
    usort($studentsWithAttendance, function($a, $b) {
        if ($a['attendance_type'] === 0 && $b['attendance_type'] !== 0) return -1;
        if ($a['attendance_type'] !== 0 && $b['attendance_type'] === 0) return 1;
        return strcmp($a['student_name'], $b['student_name']);
    });
}

$filteredCount = count($studentsWithAttendance);
$startIndex = $offset + 1;
$endIndex = min($offset + $perPage, $filteredCount);
?>
<?php include '../../components/teacher/header.php'; ?>

<?php include '../../components/sidebar.php'; ?>

<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleTeacherSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Attendance</h1>
                <p class="text-xs text-slate-400 hidden sm:block">Track student attendance</p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-poppins text-lg font-semibold text-slate-800">Student Attendance</h2>
                        <p class="text-xs text-slate-400 mt-0.5"><?= date('d M Y', strtotime($selectedDate)) ?></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <form method="get" class="flex flex-wrap items-center gap-2">
                            <input type="date" name="date" value="<?= $selectedDate ?>" onchange="this.form.submit()" class="bg-slate-50 text-slate-800 font-inter text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <select name="category" onchange="this.form.submit()" class="bg-slate-50 text-slate-800 font-inter text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $selectedCategory == $cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status" onchange="this.form.submit()" class="bg-slate-50 text-slate-800 font-inter text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>All</option>
                                <option value="not_set" <?= $selectedStatus === 'not_set' ? 'selected' : '' ?>>Not Marked</option>
                                <option value="present" <?= $selectedStatus === 'present' ? 'selected' : '' ?>>Present</option>
                                <option value="late" <?= $selectedStatus === 'late' ? 'selected' : '' ?>>Late</option>
                                <option value="absent" <?= $selectedStatus === 'absent' ? 'selected' : '' ?>>Absent</option>
                            </select>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($searchQuery) ?>">
                        </form>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="relative w-full sm:w-64">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input id="searchInput" type="text" placeholder="Search student…" class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 transition-all">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full" id="attendanceTable">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Student</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Category</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Time</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="tableBody">
                        <?php if (empty($studentsWithAttendance)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">No students found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($studentsWithAttendance as $student):
                            $attendance = $student['attendance'];
                            $hasAttendance = $attendance && isset($student['attendance_type']) && $student['attendance_type'] > 0;
                            
                            if ($hasAttendance) {
                                $type = (int)$student['attendance_type'];
                                if ($type === 1) {
                                    $typeLabel = 'Present';
                                    $typeBadge = 'bg-emerald-100 text-emerald-700';
                                } elseif ($type === 2) {
                                    $typeLabel = 'Late';
                                    $typeBadge = 'bg-amber-100 text-amber-700';
                                } elseif ($type === 3) {
                                    $typeLabel = 'Absent';
                                    $typeBadge = 'bg-red-100 text-red-600';
                                } else {
                                    $typeLabel = 'Not Marked';
                                    $typeBadge = 'bg-slate-100 text-slate-500';
                                }
                                $timeDisplay = !empty($attendance['attendance_datetime']) ? date('h:i A', strtotime($attendance['attendance_datetime'])) : '—';
                            } else {
                                $typeLabel = 'Not Marked';
                                $typeBadge = 'bg-slate-100 text-slate-500';
                                $timeDisplay = '—';
                            }
                        ?>
                        <tr class="student-row" data-name="<?= strtolower(htmlspecialchars($student['student_name'])) ?>">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold shrink-0">
                                        <?= strtoupper(substr($student['student_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($student['student_name']) ?></p>
                                        <p class="text-xs text-slate-400">#<?= $student['student_id'] ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                    <?= htmlspecialchars($student['category_name']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $typeBadge ?>">
                                    <?= $typeLabel ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600"><?= $timeDisplay ?></td>
                            <td class="px-4 py-4">
                                <?php if ($hasAttendance): ?>
                                <button onclick="editAttendance(<?= (int)$student['student_id'] ?>, '<?= $selectedDate ?>')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">
                                    Edit
                                </button>
                                <?php else: ?>
                                <div class="flex gap-1">
                                    <button onclick="markPresent(<?= (int)$student['student_id'] ?>, '<?= $selectedDate ?>')" class="px-2 py-1 text-xs font-medium rounded-lg bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition-colors">Present</button>
                                    <button onclick="openLateModal(<?= (int)$student['student_id'] ?>, '<?= $selectedDate ?>')" class="px-2 py-1 text-xs font-medium rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">Late</button>
                                    <button onclick="openAbsentModal(<?= (int)$student['student_id'] ?>, '<?= $selectedDate ?>')" class="px-2 py-1 text-xs font-medium rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors">Absent</button>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between">
                <p class="text-xs text-slate-400">
                    Showing <?= $startIndex ?>-<?= $endIndex ?> of <?= $filteredCount ?> students
                </p>
                <?php if ($totalPages > 1): ?>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&date=<?= $selectedDate ?>&category=<?= $selectedCategory ?>&status=<?= $selectedStatus ?>&search=<?= urlencode($searchQuery) ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Previous</a>
                    <?php endif; ?>
                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <a href="?page=<?= $p ?>&date=<?= $selectedDate ?>&category=<?= $selectedCategory ?>&status=<?= $selectedStatus ?>&search=<?= urlencode($searchQuery) ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $p === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 hover:bg-slate-50' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&date=<?= $selectedDate ?>&category=<?= $selectedCategory ?>&status=<?= $selectedStatus ?>&search=<?= urlencode($searchQuery) ?>" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<div id="lateModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6">
        <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">Mark Late</h3>
        <form method="post" id="lateForm">
            <input type="hidden" name="student_id" id="lateStudentId">
            <input type="hidden" name="attendance_date" id="lateDate">
            <input type="hidden" name="attendance_type" value="2">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Time</label>
                <input type="time" name="attendance_time" value="08:00" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500">
            </div>
            <div class="flex gap-3">
                <button type="submit" name="save_attendance" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-4 py-2 rounded-xl transition-colors">Save</button>
                <button type="button" onclick="closeLateModal()" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="absentModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6">
        <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">Mark Absent</h3>
        <form method="post" id="absentForm">
            <input type="hidden" name="student_id" id="absentStudentId">
            <input type="hidden" name="attendance_date" id="absentDate">
            <input type="hidden" name="attendance_type" value="3">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Notes (Optional)</label>
                <textarea name="attendance_notes" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500" placeholder="Reason for absence..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" name="save_attendance" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-inter font-medium px-4 py-2 rounded-xl transition-colors">Mark Absent</button>
                <button type="button" onclick="closeAbsentModal()" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6">
        <h3 class="font-poppins text-lg font-semibold text-slate-800 mb-4">Edit Attendance</h3>
        <form method="post" id="editForm">
            <input type="hidden" name="student_id" id="editStudentId">
            <input type="hidden" name="attendance_date" id="editDate">
            <input type="hidden" name="update_attendance" value="1">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Type</label>
                <select name="attendance_type" id="editType" onchange="toggleEditFields()" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500">
                    <option value="0">Not Marked</option>
                    <option value="1">Present</option>
                    <option value="2">Late</option>
                    <option value="3">Absent</option>
                </select>
            </div>
            <div class="mb-4" id="editTimeField">
                <label class="block text-sm font-medium text-slate-700 mb-2">Time</label>
                <input type="time" name="attendance_time" id="editTime" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500">
            </div>
            <div class="mb-4 hidden" id="editNotesField">
                <label class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
                <textarea name="attendance_notes" id="editNotes" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-4 py-2 rounded-xl transition-colors">Update</button>
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var searchInput = document.getElementById('searchInput');
    var rows = document.querySelectorAll('.student-row');
    
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        var query = searchInput.value.trim().toLowerCase();
        
        rows.forEach(function(row) {
            var name = row.getAttribute('data-name') || '';
            var match = name.includes(query);
            row.style.display = match ? '' : 'none';
        });
    });
})();

function openLateModal(studentId, date) {
    document.getElementById('lateStudentId').value = studentId;
    document.getElementById('lateDate').value = date;
    document.getElementById('lateModal').classList.remove('hidden');
    document.getElementById('lateModal').classList.add('flex');
}

function closeLateModal() {
    document.getElementById('lateModal').classList.add('hidden');
    document.getElementById('lateModal').classList.remove('flex');
}

function openAbsentModal(studentId, date) {
    document.getElementById('absentStudentId').value = studentId;
    document.getElementById('absentDate').value = date;
    document.getElementById('absentModal').classList.remove('hidden');
    document.getElementById('absentModal').classList.add('flex');
}

function closeAbsentModal() {
    document.getElementById('absentModal').classList.add('hidden');
    document.getElementById('absentModal').classList.remove('flex');
}

function markPresent(studentId, date) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input name="student_id" value="' + studentId + '"><input name="attendance_date" value="' + date + '"><input name="attendance_type" value="1"><input name="attendance_time" value="08:00"><input name="save_attendance" value="1">';
    document.body.appendChild(form);
    form.submit();
}

function editAttendance(studentId, date) {
    document.getElementById('editStudentId').value = studentId;
    document.getElementById('editDate').value = date;
    
    var formData = new FormData();
    formData.append('get_attendance', '1');
    formData.append('student_id', studentId);
    formData.append('date', date);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data) {
            document.getElementById('editType').value = data.attendance_type || '1';
            document.getElementById('editTime').value = data.attendance_time || '08:00';
            document.getElementById('editNotes').value = data.attendance_notes || '';
            toggleEditFields();
        }
    });
    
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}

function toggleEditFields() {
    var type = document.getElementById('editType').value;
    var timeField = document.getElementById('editTimeField');
    var notesField = document.getElementById('editNotesField');
    
    if (type === '0' || type === '1') {
        timeField.classList.add('hidden');
        notesField.classList.add('hidden');
    } else if (type === '2') {
        timeField.classList.remove('hidden');
        notesField.classList.add('hidden');
    } else if (type === '3') {
        timeField.classList.add('hidden');
        notesField.classList.remove('hidden');
    } else {
        timeField.classList.add('hidden');
        notesField.classList.add('hidden');
    }
}

document.getElementById('lateModal').addEventListener('click', function(e) {
    if (e.target === this) closeLateModal();
});
document.getElementById('absentModal').addEventListener('click', function(e) {
    if (e.target === this) closeAbsentModal();
});
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $studentId = $_POST['student_id'] ?? null;
    $date = $_POST['attendance_date'] ?? null;
    $type = isset($_POST['attendance_type']) ? (int)$_POST['attendance_type'] : null;
    $time = $_POST['attendance_time'] ?? '08:00';
    $notes = $_POST['attendance_notes'] ?? null;
    
    if ($studentId && $date && $type !== null) {
        if ($type === 0) {
            $stmt = $pdo->prepare("DELETE FROM attendances WHERE student_id = ? AND DATE(attendance_datetime) = ?");
            $stmt->execute([$studentId, $date]);
        } else {
            $datetime = $date . ' ' . $time . ':00';
            $stmt = $pdo->prepare("DELETE FROM attendances WHERE student_id = ? AND DATE(attendance_datetime) = ?");
            $stmt->execute([$studentId, $date]);
            $stmt = $pdo->prepare("INSERT INTO attendances (student_id, attendance_type, attendance_notes, attendance_datetime, attendance_status) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$studentId, $type, $notes, $datetime]);
        }
        $redirectUrl = "?date=$date";
        if (!empty($selectedCategory)) $redirectUrl .= "&category=$selectedCategory";
        if (!empty($selectedStatus)) $redirectUrl .= "&status=$selectedStatus";
        echo "<script>window.location.href = '$redirectUrl';</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $studentId = $_POST['student_id'] ?? null;
    $date = $_POST['attendance_date'] ?? null;
    $type = isset($_POST['attendance_type']) ? (int)$_POST['attendance_type'] : null;
    $time = $_POST['attendance_time'] ?? '08:00';
    $notes = $_POST['attendance_notes'] ?? null;
    
    if ($studentId && $date && $type !== null) {
        if ($type === 0) {
            $stmt = $pdo->prepare("DELETE FROM attendances WHERE student_id = ? AND DATE(attendance_datetime) = ?");
            $stmt->execute([$studentId, $date]);
        } else {
            $datetime = $date . ' ' . $time . ':00';
            $stmt = $pdo->prepare("DELETE FROM attendances WHERE student_id = ? AND DATE(attendance_datetime) = ?");
            $stmt->execute([$studentId, $date]);
            $stmt = $pdo->prepare("INSERT INTO attendances (student_id, attendance_type, attendance_notes, attendance_datetime, attendance_status) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$studentId, $type, $notes, $datetime]);
        }
        $redirectUrl = "?date=$date";
        if (!empty($selectedCategory)) $redirectUrl .= "&category=$selectedCategory";
        if (!empty($selectedStatus)) $redirectUrl .= "&status=$selectedStatus";
        echo "<script>window.location.href = '$redirectUrl';</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_attendance'])) {
    $studentId = $_POST['student_id'] ?? null;
    $date = $_POST['date'] ?? null;
    
    if ($studentId && $date) {
        $stmt = $pdo->prepare("SELECT * FROM attendances WHERE student_id = ? AND DATE(attendance_datetime) = ? AND attendance_status = 1");
        $stmt->execute([$studentId, $date]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        if ($attendance) {
            echo json_encode([
                'attendance_type' => $attendance['attendance_type'],
                'attendance_time' => date('H:i', strtotime($attendance['attendance_datetime'])),
                'attendance_notes' => $attendance['attendance_notes']
            ]);
        } else {
            echo json_encode(null);
        }
        exit;
    }
}
?>
</body>
</html>