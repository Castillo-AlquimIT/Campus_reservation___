<?php
// events.php
require_once 'session_check.php';

// Return approved (or pending) reservations as FullCalendar events
$statusFilter = $_GET['status'] ?? 'approved';

header('Content-Type: application/json');

$stmt = $pdo->query("SELECT id, title, start_datetime, end_datetime, room_id, equipment_id, status
                     FROM reservations
                     WHERE status IN ('approved','pending','rejected','cancelled')");

$events = [];
foreach ($stmt->fetchAll() as $r) {
  $resource = $r['room_id'] ? "Room #".$r['room_id'] : "Equip #".$r['equipment_id'];
  $events[] = [
    'id'    => $r['id'],
    'title' => $r['title']." (".$resource.")",
    'start' => $r['start_datetime'],
    'end'   => $r['end_datetime'],
    'color' => match($r['status']) {
      'approved'  => '#2e7d32', // green
      'pending'   => '#f9a825', // yellow
      'rejected'  => '#d32f2f', // red
      'cancelled' => '#757575', // gray
      default     => '#1976d2'  // blue fallback
    }
  ];
}
echo json_encode($events);

