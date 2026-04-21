<?php
/**
 * EKC Genius – Auth Guard
 *
 * Usage (at the top of any protected page):
 *   require_once __DIR__ . '/../backend/auth.php';
 *   $authUser = requireAuth('teacher');   // or 'admin' / 'parent'
 *
 * Returns:  array with keys: id, name, role
 * Exits:    no – redirects back to index.php and stops execution
 */

if (!function_exists('requireAuth')) {

    /**
     * Verify the current session belongs to the given role.
     *
     * Checks:
     *   1. $_SESSION['user_id'] and $_SESSION['user_role'] exist and match $role
     *   2. Optionally validates against the DB to confirm the record still exists
     *      and is still active (status = 1).
     *
     * @param  string $role  'admin' | 'teacher' | 'parent'
     * @param  string $redirectTo  Location header on failure (relative to site root)
     * @return array  {id, name, role}
     */
    function requireAuth(string $role, string $redirectTo = '../index.php'): array
    {
        // ── 1. Session must be started ────────────────────────────────────────
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId   = $_SESSION['user_id']   ?? null;
        $userRole = $_SESSION['user_role'] ?? null;
        $userName = $_SESSION['user_name'] ?? 'User';

        // ── 2. Basic session check ────────────────────────────────────────────
        if (!$userId || $userRole !== $role) {
            _authRedirect($redirectTo);
        }

        // ── 3. DB verification (confirm user still active) ───────────────────
        global $pdo;
        if ($pdo instanceof PDO) {
            $record = _fetchAuthRecord($pdo, $role, (int)$userId);
            if (!$record) {
                // User deleted or deactivated – destroy session and redirect
                session_unset();
                session_destroy();
                _authRedirect($redirectTo);
            }
            // Refresh name from DB in case it was updated
            $userName = $record['name'];
        }

        return [
            'id'   => (int)$userId,
            'name' => $userName,
            'role' => $role,
        ];
    }

    /**
     * Fetch the user record from the correct table based on role.
     * Returns ['name' => '...'] on success, null otherwise.
     */
    function _fetchAuthRecord(PDO $pdo, string $role, int $id): ?array
    {
        $queries = [
            'admin'   => "SELECT admin_name   AS name FROM admins   WHERE admin_id   = ? AND admin_status   = 1",
            'teacher' => "SELECT teacher_name AS name FROM teachers WHERE teacher_id = ? AND teacher_status = 1",
            'parent'  => "SELECT student_parent_name AS name FROM students WHERE student_id = ? AND student_status = 1",
        ];

        if (!isset($queries[$role])) {
            return null;
        }

        $stmt = $pdo->prepare($queries[$role]);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Redirect and stop execution.
     */
    function _authRedirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }
}
