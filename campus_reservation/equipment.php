<?php
// equipment.php
require_once 'session_check.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $stmt = $pdo->query("SELECT * FROM equipment WHERE is_active = 1 ORDER BY name");
  jsonResponse($stmt->fetchAll());
}

if ($method === 'POST') {
  if ($user['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
  $body = json_decode(file_get_contents('php://input'), true);
  $stmt = $pdo->prepare("INSERT INTO equipment (name, category, quantity, is_active) VALUES (?,?,?,1)");
  $stmt->execute([$body['name'], $body['category'] ?? null, $body['quantity'] ?? 1]);
  jsonResponse(['message' => 'Equipment created']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
