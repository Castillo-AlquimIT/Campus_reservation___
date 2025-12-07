<?php
// reports.php
require_once 'session_check.php';
header('Content-Type: application/json');

if ($user['role'] === 'student') jsonResponse(['error' => 'Forbidden'], 403);

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : null;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

$params = [];
// Include rejected and cancelled as well
$where = ["status IN ('approved','completed','rejected','cancelled')"];

if ($start && $end) { 
  $where[] = "start_datetime >= ? AND end_datetime <= ?"; 
  $params[] = $start; 
  $params[] = $end; 
}
if ($roomId) { 
  $where[] = "room_id = ?"; 
  $params[] = $roomId; 
}
if ($userId) { 
  $where[] = "user_id = ?"; 
  $params[] = $userId; 
}

$sql = "SELECT r.id, r.title, r.user_id, r.room_id, r.equipment_id, 
               r.start_datetime, r.end_datetime, r.status, 
               u.name AS user_name
        FROM reservations r
        JOIN users u ON u.id = r.user_id";

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY start_datetime ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Aggregate total hours by resource (only for approved/completed)
$summary = [
  'total_reservations' => count($rows),
  'total_hours' => 0,
  'by_room' => [],
  'by_equipment' => []
];

foreach ($rows as $row) {
  // Only count hours if actually used (approved/completed)
  if (in_array($row['status'], ['approved','completed'])) {
    $hours = (strtotime($row['end_datetime']) - strtotime($row['start_datetime'])) / 3600;
    $summary['total_hours'] += $hours;
    if ($row['room_id']) {
      $summary['by_room'][$row['room_id']] = ($summary['by_room'][$row['room_id']] ?? 0) + $hours;
    }
    if ($row['equipment_id']) {
      $summary['by_equipment'][$row['equipment_id']] = ($summary['by_equipment'][$row['equipment_id']] ?? 0) + $hours;
    }
  }
}

jsonResponse(['summary' => $summary, 'details' => $rows]);
