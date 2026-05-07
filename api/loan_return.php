<?php
require __DIR__ . '/_init.php';
require_login();

$in = read_input();
$loan_id = safe_int($in['loan_id'] ?? 0, 0);
if ($loan_id <= 0) json_out(['ok'=>false,'error'=>'loan_id invalid'], 400);

$uid = user_id();
$pdo = db();

try {
  $pdo->beginTransaction();

  $st = $pdo->prepare("SELECT id, book_id, status, user_id FROM loans WHERE id=? FOR UPDATE");
  $st->execute([$loan_id]);
  $loan = $st->fetch();
  if (!$loan) { $pdo->rollBack(); json_out(['ok'=>false,'error'=>'Împrumut inexistent'], 404); }

  // user poate returna doar împrumutul lui; librarian poate returna orice
  if ((int)$loan['user_id'] !== (int)$uid && user_role() !== 'librarian') {
    $pdo->rollBack();
    json_out(['ok'=>false,'error'=>'Acces interzis'], 403);
  }

  if ($loan['status'] !== 'borrowed') {
    $pdo->rollBack();
    json_out(['ok'=>false,'error'=>'Deja returnat'], 400);
  }

  $st2 = $pdo->prepare("UPDATE loans SET status='returned', return_date=? WHERE id=?");
  $st2->execute([date('Y-m-d'), $loan_id]);

  $st3 = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id=?");
  $st3->execute([(int)$loan['book_id']]);

  $pdo->commit();
  json_out(['ok'=>true]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['ok'=>false,'error'=>'Eroare server'], 500);
}
?>