<?php
// rooms.php
require_once 'session_check.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $stmt = $pdo->query("SELECT * FROM rooms WHERE is_active = 1 ORDER BY name");
  jsonResponse($stmt->fetchAll());
}

if ($method === 'POST') {
  if ($user['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
  $body = json_decode(file_get_contents('php://input'), true);
  $stmt = $pdo->prepare("INSERT INTO rooms (name, location, capacity, attributes, is_active) VALUES (?,?,?,?,1)");
  $stmt->execute([$body['name'], $body['location'] ?? null, $body['capacity'] ?? 0, $body['attributes'] ?? null]);
  jsonResponse(['message' => 'Room created']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
