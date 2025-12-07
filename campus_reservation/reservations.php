<?php
// reservations.php
require_once 'session_check.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'DELETE') {
  parse_str(file_get_contents("php://input"), $body);
  $reservationId = (int)($body['reservationId'] ?? 0);

  if (!$reservationId) jsonResponse(['error' => 'Missing reservationId'], 422);

  // Only admin/faculty can delete
  if ($user['role'] === 'student') jsonResponse(['error' => 'Forbidden'], 403);

  $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
  $stmt->execute([$reservationId]);

  jsonResponse(['message' => 'Reservation deleted']);
}

if ($method === 'PATCH') {
  $body = json_decode(file_get_contents('php://input'), true);
  $reservationId = (int)($body['reservationId'] ?? 0);
  $archive = $body['archive'] ?? false;

  if (!$reservationId) jsonResponse(['error' => 'Missing reservationId'], 422);
  if ($user['role'] === 'student') jsonResponse(['error' => 'Forbidden'], 403);

  if ($archive) {
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'archived' WHERE id = ?");
    $stmt->execute([$reservationId]);
    jsonResponse(['message' => 'Reservation archived']);
  }
}

if ($method === 'GET') {
  // optional filters: status, user_id, room_id, equipment_id, date range
  $params = [];
  $where = [];

  if (!empty($_GET['status'])) { $where[] = "status = ?"; $params[] = $_GET['status']; }
  if (!empty($_GET['user_id'])) { $where[] = "user_id = ?"; $params[] = (int)$_GET['user_id']; }
  if (!empty($_GET['room_id'])) { $where[] = "room_id = ?"; $params[] = (int)$_GET['room_id']; }
  if (!empty($_GET['equipment_id'])) { $where[] = "equipment_id = ?"; $params[] = (int)$_GET['equipment_id']; }
  if (!empty($_GET['start']) && !empty($_GET['end'])) {
    $where[] = "start_datetime < ? AND end_datetime > ?";
    $params[] = $_GET['end'];
    $params[] = $_GET['start'];
  }

  $sql = "SELECT r.*, u.name AS requester
          FROM reservations r
          JOIN users u ON u.id = r.user_id";
  if ($where) $sql .= " WHERE " . implode(" AND ", $where);
  $sql .= " ORDER BY start_datetime DESC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  jsonResponse($stmt->fetchAll());
}

if ($method === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true);

  $title = trim($body['title'] ?? '');
  $targetType = $body['targetType'] ?? ''; // 'room' or 'equipment'
  $targetId = (int)($body['targetId'] ?? 0);
  $start = $body['start'] ?? null;
  $end = $body['end'] ?? null;
  $notes = $body['notes'] ?? null;

  if (!$title || !$targetType || !$targetId || !$start || !$end) {
    jsonResponse(['error' => 'Missing fields'], 422);
  }

  // Conflict resolution: overlapping reservations for same resource where status blocks usage
  // overlap if (existing.start < new.end) AND (existing.end > new.start)
  $conflictSql = "";
  $params = [$end, $start, $targetId];

  if ($targetType === 'room') {
    $conflictSql = "SELECT COUNT(*) AS cnt FROM reservations
      WHERE room_id = ?
      AND status IN ('pending','approved')
      AND start_datetime < ? AND end_datetime > ?";
    $params = [$targetId, $end, $start];
  } else {
    $conflictSql = "SELECT COUNT(*) AS cnt FROM reservations
      WHERE equipment_id = ?
      AND status IN ('pending','approved')
      AND start_datetime < ? AND end_datetime > ?";
    $params = [$targetId, $end, $start];
  }

  $stmt = $pdo->prepare($conflictSql);
  $stmt->execute($params);
  $cnt = (int)$stmt->fetchColumn();

  if ($cnt > 0) {
    jsonResponse(['error' => 'Conflict detected. Choose another time or resource.'], 409);
  }

  // Create reservation (pending)
  $roomId = ($targetType === 'room') ? $targetId : null;
  $equipmentId = ($targetType === 'equipment') ? $targetId : null;

  $stmt = $pdo->prepare("INSERT INTO reservations
    (user_id, room_id, equipment_id, title, start_datetime, end_datetime, status, notes)
    VALUES (?,?,?,?,?,?, 'pending', ?)");
  $stmt->execute([$user['id'], $roomId, $equipmentId, $title, $start, $end, $notes]);
  jsonResponse(['message' => 'Reservation created and pending approval']);
}

jsonResponse(['error' => 'Method not allowed'], 405);


