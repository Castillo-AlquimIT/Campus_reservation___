<?php
// approvals.php
require_once 'session_check.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);

$body = json_decode(file_get_contents('php://input'), true);
$reservationId = (int)($body['reservationId'] ?? 0);
$toStatus = $body['toStatus'] ?? ''; // approved, rejected, completed, cancelled
$remarks = $body['remarks'] ?? null;

if (!$reservationId || !in_array($toStatus, ['approved','rejected','completed','cancelled'])) {
  jsonResponse(['error' => 'Invalid fields'], 422);
}

// Permissions: admin can change all; faculty can approve/reject if room reservations only (example rule)
if ($user['role'] === 'student') {
  jsonResponse(['error' => 'Forbidden'], 403);
}

$pdo->beginTransaction();

try {
  $stmt = $pdo->prepare("SELECT status FROM reservations WHERE id = ?");
  $stmt->execute([$reservationId]);
  $current = $stmt->fetchColumn();
  if (!$current) throw new Exception('Reservation not found');

  // If moving to approved, re-check conflict to avoid race conditions
  if ($toStatus === 'approved') {
    $stmt = $pdo->prepare("SELECT room_id, equipment_id, start_datetime, end_datetime FROM reservations WHERE id = ?");
    $stmt->execute([$reservationId]);
    $res = $stmt->fetch();

    $targetId = $res['room_id'] ?? $res['equipment_id'];
    $isRoom = !is_null($res['room_id']);

    if ($isRoom) {
      $conflictSql = "SELECT COUNT(*) FROM reservations
        WHERE room_id = ? AND id <> ? AND status IN ('approved')
        AND start_datetime < ? AND end_datetime > ?";
    } else {
      $conflictSql = "SELECT COUNT(*) FROM reservations
        WHERE equipment_id = ? AND id <> ? AND status IN ('approved')
        AND start_datetime < ? AND end_datetime > ?";
    }
    $stmt2 = $pdo->prepare($conflictSql);
    $stmt2->execute([$targetId, $reservationId, $res['end_datetime'], $res['start_datetime']]);
    $cnt = (int)$stmt2->fetchColumn();
    if ($cnt > 0) throw new Exception('Conflict on approval');
  }

  $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
  $stmt->execute([$toStatus, $reservationId]);

  $stmt = $pdo->prepare("INSERT INTO approvals (reservation_id, actor_user_id, from_status, to_status, remarks)
                         VALUES (?,?,?,?,?)");
  $stmt->execute([$reservationId, $user['id'], $current, $toStatus, $remarks]);

  $pdo->commit();
  jsonResponse(['message' => 'Status updated']);
} catch (Exception $e) {
  $pdo->rollBack();
  jsonResponse(['error' => $e->getMessage()], 409);
}
