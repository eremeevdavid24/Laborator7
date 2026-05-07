<?php
require __DIR__ . '/_init.php';
require_role('librarian');

$in = read_input();

$title = trim((string)($in['title'] ?? ''));
$author = trim((string)($in['author'] ?? ''));
$isbn = trim((string)($in['isbn'] ?? ''));
$category = trim((string)($in['category'] ?? ''));
$year = safe_int($in['year'] ?? null, 0);
$total = max(1, safe_int($in['total_copies'] ?? 1, 1));

if ($title === '' || $author === '') json_out(['ok'=>false,'error'=>'Titlu/autor lipsă'], 400);

$pdo = db();
$st = $pdo->prepare("INSERT INTO books(title,author,isbn,category,year,total_copies,available_copies)
                     VALUES(?,?,?,?,?,?,?)");
$st->execute([$title,$author,$isbn ?: null,$category ?: null, $year ?: null, $total, $total]);

json_out(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
?>
