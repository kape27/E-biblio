<?php
/**
 * E-Lib Digital Library - System Logs
 * Modern Dark Mode Glassmorphism UI
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';

$auth = new AuthManager();
$auth->requireRole('admin');

$db = DatabaseManager::getInstance();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Filters
$filterAction = $_GET['action_filter'] ?? '';
$filterUser = $_GET['user_filter'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($filterAction) {
    $whereConditions[] = "l.action LIKE ?";
    $params[] = "%{$filterAction}%";
}

if ($filterUser) {
    $whereConditions[] = "(u.username LIKE ? OR l.user_id = ?)";
    $params[] = "%{$filterUser}%";
    $params[] = is_numeric($filterUser) ? (int)$filterUser : 0;
}

if ($filterDateFrom) {
    $whereConditions[] = "DATE(l.created_at) >= ?";
    $params[] = $filterDateFrom;
}

if ($filterDateTo) {
    $whereConditions[] = "DATE(l.created_at) <= ?";
    $params[] = $filterDateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as count FROM logs l LEFT JOIN users u ON l.user_id = u.id {$whereClause}";
$totalLogs = $db->fetchOne($countSql, $params)['count'];
$totalPages = ceil($totalLogs / $perPage);

// Get logs
$sql = "SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id {$whereClause} ORDER BY l.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
$logs = $db->fetchAll($sql, $params);

// Get action types for filter
$actionTypes = $db->fetchAll("SELECT DISTINCT action FROM logs ORDER BY action");

// Get statistics
$todayLogs = $db->fetchOne("SELECT COUNT(*) as count FROM logs WHERE DATE(created_at) = CURDATE()")['count'];
$weekLogs = $db->fetchOne("SELECT COUNT(*) as count FROM logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'];
$loginAttempts = $db->fetchOne("SELECT COUNT(*) as count FROM logs WHERE action LIKE '%login%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'];
$failedLogins = $db->fetchOne("SELECT COUNT(*) as count FROM logs WHERE action = 'login_failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'];

// Get top actions
$topActions = $db->fetchAll("SELECT action, COUNT(*) as count FROM logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY action ORDER BY count DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journaux Système - E-Lib Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' }, accent: { 500: '#6366f1', 400: '#818cf8' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .glass-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); }
        .sidebar-link:hover { background: linear-gradient(90deg, rgba(239,68,68,0.2) 0%, transparent 100%); }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(239,68,68,0.3) 0%, transparent 100%); border-left: 3px solid #ef4444; }
    </style>
</head>
<body class="bg-dark-900 text-gray-100 min-h-screen">
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-dark-800 border-r border-white/5 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="p-6 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                <span class="text-xl font-bold text-white">Admin</span>
            </div>
        </div>
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span class="font-medium">Dashboard</span></a>
            <a href="users.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span class="font-medium">Utilisateurs</span></a>
            <a href="books.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span class="font-medium">Livres</span></a>
            <a href="categories.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg><span class="font-medium">Catégories</span></a>
            <a href="logs.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="font-medium">Journaux</span></a>
        </nav>
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center text-sm font-bold"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></div>
                <div class="flex-1"><p class="text-sm font-medium"><?= escape_html($_SESSION['username']) ?></p><p class="text-xs text-gray-500">Admin</p></div>
                <a href="../logout.php" class="p-2 text-gray-400 hover:text-red-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></a>
            </div>
        </div>
    </aside>

    <main class="lg:ml-64 min-h-screen">
        <header class="sticky top-0 z-30 glass border-b border-white/5">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-gray-400 hover:text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <div>
                    <h1 class="text-xl font-bold">Journaux Système</h1>
                    <p class="text-sm text-gray-400">Surveillance et audit des activités</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-white"><?= number_format($totalLogs) ?></p>
                    <p class="text-xs text-gray-400">entrées totales</p>
                </div>
            </div>
        </header>

        <div class="p-4 lg:p-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="glass-card rounded-2xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center"><svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                        <div><p class="text-xs text-gray-400">Aujourd'hui</p><p class="text-xl font-bold"><?= $todayLogs ?></p></div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-green-500/20 flex items-center justify-center"><svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
                        <div><p class="text-xs text-gray-400">7 jours</p><p class="text-xl font-bold"><?= $weekLogs ?></p></div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-yellow-500/20 flex items-center justify-center"><svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg></div>
                        <div><p class="text-xs text-gray-400">Connexions 24h</p><p class="text-xl font-bold"><?= $loginAttempts ?></p></div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center"><svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
                        <div><p class="text-xs text-gray-400">Échecs 24h</p><p class="text-xl font-bold"><?= $failedLogins ?></p></div>
                    </div>
                </div>
            </div>

            <!-- Top Actions -->
            <?php if (!empty($topActions)): ?>
            <div class="glass-card rounded-2xl p-4 mb-6">
                <h3 class="text-sm font-medium text-gray-400 mb-3">Actions fréquentes (7 jours)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                    <?php foreach ($topActions as $action): ?>
                    <div class="flex items-center justify-between bg-dark-700/30 rounded-xl px-3 py-2">
                        <span class="text-sm text-gray-300 truncate"><?= escape_html($action['action']) ?></span>
                        <span class="text-sm font-bold text-accent-400 ml-2"><?= $action['count'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="glass-card rounded-2xl p-4 mb-6">
                <form method="GET" class="flex flex-col lg:flex-row gap-4">
                    <select name="action_filter" class="flex-1 bg-dark-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-accent-500">
                        <option value="">Toutes les actions</option>
                        <?php foreach ($actionTypes as $type): ?>
                        <option value="<?= escape_html($type['action']) ?>" <?= $filterAction === $type['action'] ? 'selected' : '' ?>><?= escape_html($type['action']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="user_filter" value="<?= escape_html($filterUser) ?>" placeholder="Utilisateur..." class="flex-1 bg-dark-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500">
                    <input type="date" name="date_from" value="<?= escape_html($filterDateFrom) ?>" class="bg-dark-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-accent-500">
                    <input type="date" name="date_to" value="<?= escape_html($filterDateTo) ?>" class="bg-dark-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-accent-500">
                    <button type="submit" class="bg-dark-700 text-white px-6 py-2.5 rounded-xl hover:bg-dark-600 transition-colors">Filtrer</button>
                    <?php if ($filterAction || $filterUser || $filterDateFrom || $filterDateTo): ?>
                    <a href="logs.php" class="px-4 py-2.5 text-gray-400 hover:text-white">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Logs Table -->
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-dark-700/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase">Date/Heure</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase">Utilisateur</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase">Action</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase">Détails</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Aucune entrée trouvée.</td></tr>
                            <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-400"><?= escape_html(date('d/m/Y H:i:s', strtotime($log['created_at']))) ?></td>
                                <td class="px-6 py-4 text-sm"><?= escape_html($log['username'] ?? 'Système') ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium <?php
                                        if (str_contains($log['action'], 'success') || str_contains($log['action'], 'created')) echo 'bg-green-500/20 text-green-400';
                                        elseif (str_contains($log['action'], 'failed') || str_contains($log['action'], 'denied') || str_contains($log['action'], 'deleted')) echo 'bg-red-500/20 text-red-400';
                                        elseif (str_contains($log['action'], 'updated') || str_contains($log['action'], 'changed')) echo 'bg-yellow-500/20 text-yellow-400';
                                        else echo 'bg-gray-500/20 text-gray-400';
                                    ?>"><?= escape_html($log['action']) ?></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500" title="<?= escape_html($log['details'] ?? '') ?>"><?= escape_html(substr($log['details'] ?? '', 0, 50)) ?><?= strlen($log['details'] ?? '') > 50 ? '...' : '' ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?= escape_html($log['ip_address'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-white/5 flex items-center justify-between">
                    <p class="text-sm text-gray-400">Page <?= $page ?> sur <?= $totalPages ?></p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&action_filter=<?= urlencode($filterAction) ?>&user_filter=<?= urlencode($filterUser) ?>&date_from=<?= urlencode($filterDateFrom) ?>&date_to=<?= urlencode($filterDateTo) ?>" class="px-4 py-2 bg-dark-700 rounded-lg text-gray-300 hover:bg-dark-600">← Précédent</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&action_filter=<?= urlencode($filterAction) ?>&user_filter=<?= urlencode($filterUser) ?>&date_from=<?= urlencode($filterDateFrom) ?>&date_to=<?= urlencode($filterDateTo) ?>" class="px-4 py-2 bg-dark-700 rounded-lg text-gray-300 hover:bg-dark-600">Suivant →</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>
