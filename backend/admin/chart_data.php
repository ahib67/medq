<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

// Only the admin may read this data
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin only']);
    exit;
}

// CHART 1: tokens booked per day, last 7 days.
// The query returns only days that have tokens, so we fill the empty days with 0 ourselves.
$counts = $pdo->query("SELECT token_date, COUNT(*) FROM tokens
                        WHERE token_date >= CURDATE() - INTERVAL 6 DAY AND token_date <= CURDATE()
                        GROUP BY token_date")->fetchAll(PDO::FETCH_KEY_PAIR);

$day_labels = [];
$day_values = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i day"));
    $day_labels[] = date('D d M', strtotime($date));
    $day_values[] = (int)($counts[$date] ?? 0);
}

// CHART 2: today's tokens by status (fixed order so the colours never change)
$by_status = $pdo->query("SELECT status, COUNT(*) FROM tokens
                           WHERE token_date = CURDATE() GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$status_labels = ['waiting', 'in_consultation', 'completed', 'cancelled', 'no_show'];
$status_values = [];
foreach ($status_labels as $s) {
    $status_values[] = (int)($by_status[$s] ?? 0);
}

// CHART 3: tokens per doctor, last 30 days
$rows = $pdo->query("SELECT u.name, COUNT(t.token_id) AS total
                       FROM doctors d
                       JOIN users u ON u.user_id = d.user_id
                       LEFT JOIN tokens t ON t.doctor_id = d.doctor_id
                                          AND t.token_date >= CURDATE() - INTERVAL 30 DAY
                      GROUP BY d.doctor_id, u.name
                      ORDER BY u.name")->fetchAll();

$doc_labels = [];
$doc_values = [];
foreach ($rows as $r) {
    $doc_labels[] = $r['name'];
    $doc_values[] = (int)$r['total'];
}

echo json_encode([
    'days'    => ['labels' => $day_labels,    'values' => $day_values],
    'status'  => ['labels' => $status_labels, 'values' => $status_values],
    'doctors' => ['labels' => $doc_labels,    'values' => $doc_values],
    'time'    => date('H:i:s'),
]);
