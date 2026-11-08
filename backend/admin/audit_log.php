<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_role('admin');

// Filters from the URL
$user_id = (int)($_GET['user_id'] ?? 0);
$q       = trim($_GET['q'] ?? '');
$from    = $_GET['from'] ?? '';
$to      = $_GET['to'] ?? '';

// Build the WHERE part step by step
$where  = [];
$params = [];
if ($user_id > 0) { $where[] = 'a.user_id = ?';      $params[] = $user_id; }
if ($q !== '')    { $where[] = 'a.action LIKE ?';    $params[] = '%' . $q . '%'; }
if ($from !== '') { $where[] = 'a.created_at >= ?';  $params[] = $from . ' 00:00:00'; }
if ($to !== '')   { $where[] = 'a.created_at <= ?';  $params[] = $to . ' 23:59:59'; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Paging: 20 rows per page
$per_page = 20;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

// How many rows match in total?
$stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log a $where_sql");
$stmt->execute($params);
$total       = (int)$stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

// The rows of this page (per_page and offset are integers we calculated ourselves)
$stmt = $pdo->prepare("SELECT a.log_id, a.created_at, a.action, u.name AS user_name, u.role
                         FROM audit_log a
                         LEFT JOIN users u ON u.user_id = a.user_id
                         $where_sql
                        ORDER BY a.log_id DESC
                        LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Users who appear in the log, for the dropdown
$users = $pdo->query('SELECT user_id, name, role FROM users
                       WHERE user_id IN (SELECT DISTINCT user_id FROM audit_log WHERE user_id IS NOT NULL)
                       ORDER BY name')->fetchAll();

// Keep the filters in the Previous / Next links
$filters = ['user_id' => $user_id, 'q' => $q, 'from' => $from, 'to' => $to];

$page_title = 'Audit log';
include '../includes/header.php';
include 'menu.php';
?>
<h2>Audit log</h2>

<form method="get" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
    <label>User
        <select name="user_id">
            <option value="0">All users</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= e($u['user_id']) ?>" <?= $user_id == $u['user_id'] ? 'selected' : '' ?>>
                    <?= e($u['name']) ?> (<?= e($u['role']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Action contains <input type="text" name="q" value="<?= e($q) ?>"></label>
    <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
    <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
    <button type="submit">Filter</button>
    <a href="audit_log.php">Clear</a>
</form>

<p><?= e($total) ?> record(s) found.</p>
<table>
    <tr><th>#</th><th>Time</th><th>User</th><th>Role</th><th>Action</th></tr>
    <?php foreach ($logs as $l): ?>
        <tr>
            <td><?= e($l['log_id']) ?></td>
            <td><?= e($l['created_at']) ?></td>
            <td><?= e($l['user_name'] ?? '(deleted user)') ?></td>
            <td><?= e($l['role'] ?? '-') ?></td>
            <td><?= e($l['action']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="5">No records.</td></tr><?php endif; ?>
</table>

<p>
    <?php if ($page > 1): ?>
        <a href="?<?= e(http_build_query($filters + ['page' => $page - 1])) ?>">&laquo; Previous</a>
    <?php endif; ?>
    Page <?= e($page) ?> of <?= e($total_pages) ?>
    <?php if ($page < $total_pages): ?>
        <a href="?<?= e(http_build_query($filters + ['page' => $page + 1])) ?>">Next &raquo;</a>
    <?php endif; ?>
</p>
<?php include '../includes/footer.php'; ?>
