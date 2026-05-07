<?php
require __DIR__ . '/_init.php';
require_role('librarian');

$pdo = db();

$st = $pdo->query("
  SELECT l.id, l.loan_date, l.due_date, l.return_date, l.status,
         u.name AS user_name, u.email,
         b.title, b.author
  FROM loans l
  JOIN users u ON u.id = l.user_id
  JOIN books b ON b.id = l.book_id
  ORDER BY l.status='borrowed' DESC, l.loan_date DESC
");
$rows = $st->fetchAll();

json_out(['ok'=>true,'items'=>$rows]);
?>
