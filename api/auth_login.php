<?php
require __DIR__ . '/_init.php';

$in = read_input();
$email = trim((string)($in['email'] ?? ''));
$pass  = (string)($in['password'] ?? '');

if ($email === '' || $pass === '') json_out(['ok'=>false,'error'=>'Email/parolă lipsă'], 400);

$pdo = db();
$st = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
$st->execute([$email]);
$u = $st->fetch();

if (!$u or !password_verify($pass, $u['password'])) {
  json_out(['ok'=>false,'error'=>'Date invalide'], 401);
}

$_SESSION['uid'] = (int)$u['id'];
$_SESSION['role'] = $u['role'];
$_SESSION['name'] = $u['name'];

json_out([
  'ok'=>true,
  'user'=>[
    'id'=>(int)$u['id'],
    'name'=>$u['name'],
    'email'=>$u['email'],
    'role'=>$u['role']
  ]
]);
?>