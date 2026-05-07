<?php
require __DIR__ . '/_init.php';
require_login();

$uid = user_id();
$pdo = db();

$st = $pdo->prepare("
  SELECT l.id, l.loan_date, l.due_date, l.return_date, l.status,
         b.title, b.author, b.category
  FROM loans l
  JOIN books b ON b.id = l.book_id
  WHERE l.user_id = ?
  ORDER BY l.status='borrowed' DESC, l.loan_date DESC
");
$st->execute([$uid]);
$rows = $st->fetchAll();

json_out(['ok'=>true,'items'=>$rows]);
?>