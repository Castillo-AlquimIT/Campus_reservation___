<?php
// session_check.php
header('Content-Type: application/json');

require_once 'db.php';
if (!isset($_SESSION['user'])) {
  jsonResponse(['error' => 'Unauthorized'], 401);
}
$user = $_SESSION['user'];

