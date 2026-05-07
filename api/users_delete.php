<?php
require __DIR__ . '/_init.php';

$me = session_user();
if (!$me || $me['role'] !== 'admin') {
  json_out(['ok'=>false,'error'=>'Acces interzis'], 403);
}

$in = read_input();
$id = (int)($in['id'] ?? 0);

if ($id <= 0) {
  json_out(['ok'=>false,'error'=>'ID invalid'], 400);
}

if ($me['id'] == $id) {
  json_out(['ok'=>false,'error'=>'Nu poți șterge propriul cont'], 400);
}

$pdo = db();
$st = $pdo->prepare("DELETE FROM users WHERE id = ?");
$st->execute([$id]);

json_out(['ok'=>true,'message'=>'Utilizator șters']);
?>
