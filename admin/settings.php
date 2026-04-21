<?php
session_start();
require_once '../config/connect.php';
require_once '../backend/auth.php';
$authUser = requireAuth('admin');
$pageTitle = 'Settings';
$cssDepth = '../public/css';
$location_index = '..';

$message = '';
$error = '';

$envFile = __DIR__ . '/../.env';
$currentPasskey = '';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'TEACHER_PASSKEY=') === 0) {
            $currentPasskey = str_replace('TEACHER_PASSKEY=', '', $line);
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_passkey'])) {
    $newPasskey = trim($_POST['new_passkey'] ?? '');
    $confirmPasskey = trim($_POST['confirm_passkey'] ?? '');
    
    if (empty($newPasskey)) {
        $error = 'Passkey cannot be empty.';
    } elseif ($newPasskey !== $confirmPasskey) {
        $error = 'Passkey and confirmation do not match.';
    } elseif (strlen($newPasskey) < 4) {
        $error = 'Passkey must be at least 4 characters.';
    } else {
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES);
            $found = false;
            foreach ($lines as &$line) {
                if (strpos($line, 'TEACHER_PASSKEY=') === 0) {
                    $line = 'TEACHER_PASSKEY=' . $newPasskey;
                    $found = true;
                    break;
                }
            }
            if ($found) {
                file_put_contents($envFile, implode("\n", $lines));
                $message = 'Passkey updated successfully!';
                $currentPasskey = $newPasskey;
            } else {
                $error = 'Passkey entry not found in .env file.';
            }
        } else {
            $error = '.env file not found.';
        }
    }
}
?>
<?php include '../components/teacher/header.php'; ?>

<?php include '../components/sidebar.php'; ?>

<main class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="font-poppins text-xl font-semibold text-slate-800">Settings</h1>
                <p class="text-xs text-slate-400 hidden sm:block"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
    </header>

    <div class="flex-1 p-4 lg:p-8 space-y-6">
        <?php if ($message): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Teacher Passkey Settings -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-poppins text-lg font-semibold text-slate-800">Teacher Passkey</h2>
                <p class="text-xs text-slate-400 mt-0.5">Manage the passkey required for teacher login</p>
            </div>
            
            <div class="p-6">
                <div class="mb-6 p-4 bg-slate-50 rounded-xl">
                    <p class="text-sm text-slate-600">
                        <span class="font-medium">Current Passkey:</span> 
                        <span class="font-mono text-slate-800"><?= htmlspecialchars($currentPasskey) ?></span>
                    </p>
                </div>

                <form method="POST" class="space-y-4 max-w-md">
                    <input type="hidden" name="update_passkey" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">New Passkey</label>
                        <input type="text" name="new_passkey" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter new passkey">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Confirm Passkey</label>
                        <input type="text" name="confirm_passkey" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Confirm new passkey">
                    </div>
                    
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-inter font-medium px-6 py-2.5 rounded-xl transition-colors">
                        Update Passkey
                    </button>
                </form>
            </div>
        </div>

        <!-- System Info -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-poppins text-lg font-semibold text-slate-800">System Information</h2>
                <p class="text-xs text-slate-400 mt-0.5">Application details</p>
            </div>
            
            <div class="p-6 space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-slate-500">Application</span>
                    <span class="text-sm font-medium text-slate-800">EKC Genius</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-slate-500">Version</span>
                    <span class="text-sm font-medium text-slate-800">1.0.0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-slate-500">PHP Version</span>
                    <span class="text-sm font-medium text-slate-800"><?= PHP_VERSION ?></span>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>