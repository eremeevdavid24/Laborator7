<?php
require __DIR__ . '/_init.php';
require_role('librarian');

$in = read_input();
$id = safe_int($in['id'] ?? 0, 0);
if ($id <= 0) json_out(['ok'=>false,'error'=>'ID invalid'], 400);

$pdo = db();

// nu ștergem dacă există împrumuturi active
$st0 = $pdo->prepare("SELECT COUNT(*) AS c FROM loans WHERE book_id=? AND status='borrowed'");
$st0->execute([$id]);
if ((int)($st0->fetch()['c'] ?? 0) > 0) {
  json_out(['ok'=>false,'error'=>'Nu poți șterge: există împrumuturi active.'], 400);
}

$st = $pdo->prepare("DELETE FROM books WHERE id=?");
$st->execute([$id]);

json_out(['ok'=>true]);
?>