<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}
session_start();

function json_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function read_input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $arr = json_decode($raw ?: '[]', true);
    return is_array($arr) ? $arr : [];
  }
  return $_POST ?? [];
}

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $cfg = require __DIR__ . '/config.php';
  $dsn = "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4";
  $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

function user_id(): ?int {
  return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
}

function user_role(): ?string {
  return $_SESSION['role'] ?? null;
}

function require_login(): void {
  if (!user_id()) json_out(['ok' => false, 'error' => 'Neautentificat'], 401);
}

function require_role(string $role): void {
  require_login();
  if (user_role() !== $role) json_out(['ok' => false, 'error' => 'Acces interzis'], 403);
}

function safe_int($v, int $def = 0): int {
  if ($v === null || $v === '') return $def;
  if (!is_numeric($v)) return $def;
  return (int)$v;
}
?>