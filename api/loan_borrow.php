<?php
require __DIR__ . '/_init.php';
require_login();

$in = read_input();
$book_id = safe_int($in['book_id'] ?? 0, 0);
if ($book_id <= 0) json_out(['ok'=>false,'error'=>'book_id invalid'], 400);

$uid = user_id();
$pdo = db();

try {
  $pdo->beginTransaction();

  // blocăm rândul cărții (prevent dublu împrumut simultan)
  $st = $pdo->prepare("SELECT available_copies FROM books WHERE id=? FOR UPDATE");
  $st->execute([$book_id]);
  $b = $st->fetch();
  if (!$b) { $pdo->rollBack(); json_out(['ok'=>false,'error'=>'Cartea nu există'], 404); }

  if ((int)$b['available_copies'] <= 0) {
    $pdo->rollBack();
    json_out(['ok'=>false,'error'=>'Nu există exemplare disponibile'], 400);
  }

  // scădem disponibil
  $st2 = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id=?");
  $st2->execute([$book_id]);

  $loan_date = date('Y-m-d');
  $due_date = date('Y-m-d', strtotime('+14 days'));

  $st3 = $pdo->prepare("INSERT INTO loans(user_id, book_id, loan_date, due_date, status)
                        VALUES(?,?,?,?, 'borrowed')");
  $st3->execute([$uid, $book_id, $loan_date, $due_date]);

  $pdo->commit();
  json_out(['ok'=>true,'loan_id'=>(int)$pdo->lastInsertId(), 'due_date'=>$due_date]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['ok'=>false,'error'=>'Eroare server'], 500);
}
?>
