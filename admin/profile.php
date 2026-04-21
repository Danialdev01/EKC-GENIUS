<?php
session_start();
require_once '../config/connect.php';
require_once '../backend/auth.php';
$location_index = "..";
$authUser = requireAuth('admin');
$pageTitle = 'Profile';
$cssDepth = '../public/css';

$adminId = $authUser['id'];
$message = '';

$stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ? AND admin_status = 1");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $admin_name = trim($_POST['admin_name'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!empty($admin_name)) {
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $message = 'Passwords do not match!';
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET admin_name = ?, admin_hash_password = ?, admin_updated_at = NOW() WHERE admin_id = ?");
                $stmt->execute([$admin_name, $hashed, $adminId]);
                $message = 'Profile and password updated successfully!';
                
                $_SESSION['user_name'] = $admin_name;
            }
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET admin_name = ?, admin_updated_at = NOW() WHERE admin_id = ?");
            $stmt->execute([$admin_name, $adminId]);
            $message = 'Profile updated successfully!';
            
            $_SESSION['user_name'] = $admin_name;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ? AND admin_status = 1");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<?php include '../components/teacher/header.php'; ?>
<?php include '../components/sidebar.php'; ?>

<main class="lg:ml-64 min-h-screen">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-500 hover:text-slate-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="font-poppins text-xl font-semibold">Profile</h1>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-slate-500"><?= date('F d, Y'); ?></span>
        </div>
    </header>

    <div class="p-4 lg:p-8">
        <?php if ($message): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="font-poppins text-lg font-semibold text-slate-800">Admin Profile</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Update your personal information</p>
                </div>
                
                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-trust-100 text-trust-600 flex items-center justify-center text-2xl font-bold">
                            <?= strtoupper(substr($admin['admin_name'] ?? '', 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Profile Photo</p>
                            <p class="text-xs text-slate-400">Initials will be displayed</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Full Name</label>
                        <input type="text" name="admin_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Enter your name" value="<?= htmlspecialchars($admin['admin_name'] ?? '') ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                        <input type="email" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500" value="<?= htmlspecialchars($admin['admin_email'] ?? '') ?>" disabled>
                        <p class="text-xs text-slate-400 mt-1">Contact database admin to change email</p>
                    </div>
                    
                    <div class="pt-4 border-t border-slate-200">
                        <p class="text-sm font-medium text-slate-700 mb-3">Change Password (Optional)</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">New Password</label>
                        <input type="password" name="new_password" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Leave blank to keep current">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Confirm new password">
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-trust-600 hover:bg-trust-700 text-white font-inter font-medium py-3 rounded-xl transition-colors">
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