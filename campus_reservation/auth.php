<?php
// auth.php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true);
  $email = trim($body['email'] ?? '');
  $password = $body['password'] ?? '';

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['error' => 'Invalid credentials'], 401);
  }
  $_SESSION['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role']
  ];
  jsonResponse(['message' => 'Logged in', 'user' => $_SESSION['user']]);
}

if ($method === 'DELETE') {
  session_destroy();
  jsonResponse(['message' => 'Logged out']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
