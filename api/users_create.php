<?php
require __DIR__ . '/_init.php';

$me = session_user();
if (!$me || $me['role'] !== 'admin') {
  json_out(['ok'=>false,'error'=>'Acces interzis'], 403);
}

$in = read_input();
$name = trim((string)($in['name'] ?? ''));
$email = trim((string)($in['email'] ?? ''));
$role = (string)($in['role'] ?? 'user');
$password = (string)($in['password'] ?? '');

if ($name === '' || $email === '') {
  json_out(['ok'=>false,'error'=>'Nume/email lipsă'], 400);
}

if (!in_array($role, ['user', 'admin'])) {
  json_out(['ok'=>false,'error'=>'Rol invalid'], 400);
}

if ($password === '') {
  json_out(['ok'=>false,'error'=>'Parola lipsă'], 400);
}

$pdo = db();

// Check if email exists
$st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$st->execute([$email]);
if ($st->fetch()) {
  json_out(['ok'=>false,'error'=>'Email deja există'], 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$st = $pdo->prepare("
  INSERT INTO users (name, email, password_hash, role, password_change_required)
  VALUES (?, ?, ?, ?, 1)
");
$st->execute([$name, $email, $hash, $role]);

json_out([
  'ok'=>true,
  'message'=>'Utilizator creat cu succes. Va trebui să-și schimbe parola la prima conectare.'
]);
?>
