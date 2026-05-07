<?php
require __DIR__ . '/_init.php';

$q = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$onlyAvail = ($_GET['onlyAvail'] ?? '') === '1';

$page = max(1, safe_int($_GET['page'] ?? 1, 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where = [];
$args = [];

if ($q !== '') {
  $where[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
  $like = "%$q%";
  $args[] = $like; $args[] = $like; $args[] = $like;
}
if ($category !== '') {
  $where[] = "category = ?";
  $args[] = $category;
}
if ($onlyAvail) {
  $where[] = "available_copies > 0";
}

$wsql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$pdo = db();

$stc = $pdo->prepare("SELECT COUNT(*) AS c FROM books $wsql");
$stc->execute($args);
$total = (int)($stc->fetch()['c'] ?? 0);

$st = $pdo->prepare("SELECT id,title,author,isbn,category,year,total_copies,available_copies
                     FROM books $wsql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$st->execute($args);
$rows = $st->fetchAll();

json_out(['ok'=>true,'items'=>$rows,'total'=>$total,'page'=>$page,'pages'=> (int)ceil($total/$limit)]);
