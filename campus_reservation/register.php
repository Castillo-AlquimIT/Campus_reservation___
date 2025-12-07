<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true);
  $name = trim($body['name'] ?? '');
  $email = trim($body['email'] ?? '');
  $password = $body['password'] ?? '';
  $role = $body['role'] ?? 'student';

  if (!$name || !$email || !$password) {
    http_response_code(422);
    echo json_encode(['error' => 'All fields are required']);
    exit;
  }

  // Hash password
  $hash = password_hash($password, PASSWORD_BCRYPT);

  try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)");
    $stmt->execute([$name, $email, $hash, $role]);
    echo json_encode(['message' => 'Registration successful']);
  } catch (Exception $e) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already exists']);
  }
}
