<?php
require __DIR__ . '/_init.php';

$me = session_user();
if (!$me || $me['role'] !== 'admin') {
  json_out(['ok'=>false,'error'=>'Acces interzis'], 403);
}

$pdo = db();
$st = $pdo->query("SELECT id, name, email, role, password_change_required, created_at FROM users ORDER BY id DESC");
$users = $st->fetchAll();

json_out([
  'ok'=>true,
  'items'=>$users
]);
?>
