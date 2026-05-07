<?php
require __DIR__ . '/_init.php';

$me = session_user();
if (!$me) {
  json_out(['ok'=>false,'error'=>'Neautentificat'], 401);
}

$in = read_input();
$old_password = (string)($in['old_password'] ?? '');
$new_password = (string)($in['new_password'] ?? '');

if ($old_password === '' || $new_password === '') {
  json_out(['ok'=>false,'error'=>'Parolele lipsă'], 400);
}

if (strlen($new_password) < 4) {
  json_out(['ok'=>false,'error'=>'Noua parolă trebuie să aibă cel puțin 4 caractere'], 400);
}

$pdo = db();
$st = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$st->execute([$me['id']]);
$u = $st->fetch();

if (!$u || !password_verify($old_password, $u['password_hash'])) {
  json_out(['ok'=>false,'error'=>'Parola veche incorectă'], 401);
}

$new_hash = password_hash($new_password, PASSWORD_BCRYPT);
$st = $pdo->prepare("UPDATE users SET password_hash = ?, password_change_required = 0 WHERE id = ?");
$st->execute([$new_hash, $me['id']]);

json_out(['ok'=>true,'message'=>'Parolă schimbată cu succes']);
?>
