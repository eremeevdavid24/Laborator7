<?php
require __DIR__ . '/_init.php';
require_role('librarian');

$in = read_input();
$id = safe_int($in['id'] ?? 0, 0);
if ($id <= 0) json_out(['ok'=>false,'error'=>'ID invalid'], 400);

$title = trim((string)($in['title'] ?? ''));
$author = trim((string)($in['author'] ?? ''));
$isbn = trim((string)($in['isbn'] ?? ''));
$category = trim((string)($in['category'] ?? ''));
$year = safe_int($in['year'] ?? null, 0);
$total = max(1, safe_int($in['total_copies'] ?? 1, 1));

$pdo = db();

// luăm cartea curentă ca să recalculăm available dacă total se schimbă
$st0 = $pdo->prepare("SELECT total_copies, available_copies FROM books WHERE id=?");
$st0->execute([$id]);
$cur = $st0->fetch();
if (!$cur) json_out(['ok'=>false,'error'=>'Cartea nu există'], 404);

$borrowed = (int)$cur['total_copies'] - (int)$cur['available_copies'];
if ($total < $borrowed) {
  json_out(['ok'=>false,'error'=>"Nu poți seta total_copies sub numărul împrumutate ($borrowed)."], 400);
}
$newAvail = $total - $borrowed;

$st = $pdo->prepare("UPDATE books SET title=?, author=?, isbn=?, category=?, year=?, total_copies=?, available_copies=? WHERE id=?");
$st->execute([
  $title ?: 'Fără titlu',
  $author ?: 'Fără autor',
  $isbn ?: null,
  $category ?: null,
  $year ?: null,
  $total,
  $newAvail,
  $id
]);

json_out(['ok'=>true]);
?>